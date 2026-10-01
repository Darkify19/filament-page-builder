<?php

namespace CarlJanzell\FilamentPageBuilder\Filament\Pages;

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use CarlJanzell\FilamentPageBuilder\Editable;
use CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns\EditsBlockStyle;
use CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns\PreviewsPage;
use CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns\ResizesBlocks;
use CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns\UsesClipboard;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use CarlJanzell\FilamentPageBuilder\PageBuilder;
use CarlJanzell\FilamentPageBuilder\Support\BlockHistory;
use CarlJanzell\FilamentPageBuilder\Support\BlockStateNormaliser;
use CarlJanzell\FilamentPageBuilder\Support\BlockTree;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The drag-and-drop canvas.
 *
 * Subclass this in the consuming application to bind it to a resource:
 *
 *     class DesignPage extends BaseDesignPage
 *     {
 *         protected static string $resource = PageResource::class;
 *     }
 *
 * Blocks are mutated in memory and persisted on an explicit save. Making every drag a
 * database write would put a round trip in the middle of a drag gesture.
 */
abstract class DesignPage extends Page
{
    use EditsBlockStyle;
    use InteractsWithRecord;
    use PreviewsPage;
    use ResizesBlocks;
    use UsesClipboard;

    protected string $view = 'page-builder::design';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $blocks = [];

    public ?string $selectedId = null;

    /**
     * Form state for the selected block only.
     *
     * @var array<string, mixed>
     */
    public array $blockData = [];

    /**
     * Style tokens for the selected block only.
     *
     * @var array<string, mixed>
     */
    public array $blockSettings = [];

    public bool $isDirty = false;

    /**
     * The last persisted tree, used so undo back to it clears the dirty flag.
     *
     * Public so Livewire dehydrates it: a protected snapshot would reset on every
     * request and undo-to-saved would still look dirty.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $savedBlocks = [];

    /**
     * `updated_at` of the record when the canvas opened or last saved, for the lock.
     */
    public ?string $loadedUpdatedAt = null;

    /**
     * Anchor id for the selected block (passthrough key, not a style token).
     */
    public string $blockAnchor = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $raw = $this->record->{$this->blocksAttribute()} ?? [];
        $hadDuplicateIds = $this->rawHasDuplicateIds($raw);

        $this->blocks = $this->prepareBlocks($raw);
        $this->loadedUpdatedAt = optional($this->record->updated_at)->toJSON();

        $this->history()->clear();

        if ($hadDuplicateIds) {
            $this->isDirty = true;
            $this->savedBlocks = [];

            Notification::make()
                ->title(__('page-builder::chrome.duplicate_ids_repaired'))
                ->warning()
                ->send();

            return;
        }

        $this->markSaved();
    }

    public function getTitle(): string
    {
        return __('page-builder::chrome.design_title', ['title' => $this->getRecordTitle()]);
    }

    /**
     * The Filament page heading stays empty so the editor chrome can own the top of
     * the viewport. The browser tab still uses getTitle().
     */
    public function getHeading(): string|Htmlable|null
    {
        return '';
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Screen;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraBodyAttributes(): array
    {
        $attributes = parent::getExtraBodyAttributes();
        $attributes['class'] = trim(($attributes['class'] ?? '').' fpb-edit-mode');

        return $attributes;
    }

    /**
     * Deliberately not `fpb-page`: that class marks the public page wrapper, and the
     * rules hung off it strip a slot back to a bare grid cell. Putting it on the editor
     * too erased the drop wells the canvas needs.
     *
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['fpb-editor-page'];
    }

    /**
     * Where "Back" goes — the resource list if it exists, otherwise the form editor.
     */
    public function exitUrl(): ?string
    {
        $resource = static::getResource();

        if ($resource::hasPage('index')) {
            return $resource::getUrl('index');
        }

        return $this->formEditorUrl();
    }

    public function exitLabel(): string
    {
        return __('page-builder::chrome.back_to', ['resource' => static::getResource()::getBreadcrumb()]);
    }

    /**
     * Every string `page-builder.js` needs, resolved once per request.
     *
     * The canvas ships as plain ES2020 with no bundler and no import map, so the only way
     * for it to speak the editor's language is for the server to hand the translations
     * over. The whole `js` group goes across rather than a hand-picked subset: a key the
     * view forgot to pass is English on a screen nobody asked for it on, whereas a key it
     * does not use yet costs a few hundred bytes.
     *
     * Asking for a whole group returns that locale's file as it stands, without the
     * key-by-key fallback a single `__()` gets. So the locale is laid over English (and
     * over the application's fallback locale): a string not translated yet reads in
     * English, as it does everywhere else in the editor, instead of as the key `t()`
     * shows for a gap.
     *
     * @return array<string, string>
     */
    public function canvasStrings(): array
    {
        $translator = app('translator');
        $strings = [];

        foreach (array_unique(['en', $translator->getFallback(), $translator->getLocale()]) as $locale) {
            $group = $translator->get('page-builder::js', [], $locale, false);

            if (is_array($group)) {
                $strings = array_replace($strings, $group);
            }
        }

        return array_map(fn (mixed $value): string => (string) $value, $strings);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    /**
     * Which attribute on the record holds the blocks.
     *
     * A record that uses HasBlocks answers for itself, which is what lets one panel host
     * two models that name the column differently. Anything else falls back to the
     * panel's configuration.
     */
    public function blocksAttribute(): string
    {
        $record = $this->getRecord();

        if (method_exists($record, 'blocksAttribute')) {
            return $record->blocksAttribute();
        }

        return FilamentPageBuilderPlugin::get()->getBlocksAttribute();
    }

    public function canvasStylesView(): ?string
    {
        return FilamentPageBuilderPlugin::get()->getCanvasStylesView();
    }

    /**
     * The resource's form editor, when it has one.
     *
     * A resource is not obliged to expose an edit page — the canvas may be the only
     * editing surface — so the toolbar link is conditional rather than assumed.
     */
    public function formEditorUrl(): ?string
    {
        $resource = static::getResource();

        if (! $resource::hasPage('edit')) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    public function registry(): BlockRegistry
    {
        return app(BlockRegistry::class);
    }

    public function normaliser(): BlockStateNormaliser
    {
        return app(BlockStateNormaliser::class);
    }

    public function history(): BlockHistory
    {
        return new BlockHistory(session()->driver(), $this->getId());
    }

    /**
     * Record the state a mutation is about to replace.
     *
     * Called by each mutation immediately before it changes anything, and only once it
     * knows it will: recording a step that turns out to be a no-op would make the first
     * undo appear to do nothing.
     */
    protected function remember(): void
    {
        $this->history()->push($this->blocks);
    }

    /* ── Reading ───────────────────────────────────────── */

    /**
     * Blocks with ids guaranteed and their own keys left intact.
     *
     * Types the registry does not know are kept rather than dropped. The canvas cannot
     * render or edit them, but it loads and saves the whole array, so pruning here would
     * mean opening a page holding a retired block type and pressing Save destroyed that
     * content. Skipping an unknown type at render time is correct; doing it at persist
     * time is data loss.
     *
     * Anything the application has attached to a block beyond id/type/data is carried
     * through untouched for the same reason.
     *
     * Deliberately not named hydrateBlocks: Livewire treats hydrate{Property} as a
     * lifecycle hook and would try to call it on every request.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prepareBlocks(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        return BlockTree::hydrate($blocks);
    }

    /**
     * Blocks ready to render on the canvas, with editing state reconciled.
     *
     * Flat, in document order. The canvas itself walks `rootBlocks` so nested
     * children render inside their parent rather than as extra siblings.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRenderableBlocksProperty(): array
    {
        return array_map(fn (array $block): array => $this->decorate($block, withChildren: false), $this->blocks);
    }

    /**
     * Top-level blocks, each carrying its nested children already decorated.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRootBlocksProperty(): array
    {
        return $this->decorateChildren(null);
    }

    /**
     * Outline of the page, for the structure list.
     *
     * @return array<int, array{id: string, label: string, type: string, children: array<int, mixed>}>
     */
    public function getStructureProperty(): array
    {
        return $this->structureFrom($this->rootBlocks);
    }

    /**
     * Palette items grouped by category: Layout, Content, Media, Design, then the rest.
     *
     * A category an application invents falls back to its own name, since a key nothing
     * translates would come back as `marketing` on the palette and read as a bug.
     *
     * @return array<string, array{label: string, items: array<int, array{type: string, label: string, icon: ?string, category: string, description: ?string}>}>
     */
    public function getPaletteGroupsProperty(): array
    {
        $labels = [
            'layout' => __('page-builder::chrome.layout'),
            'content' => __('page-builder::chrome.content'),
            'media' => __('page-builder::chrome.media'),
            'design' => __('page-builder::chrome.design'),
            'developer' => __('page-builder::chrome.developer'),
            'blocks' => __('page-builder::chrome.blocks'),
        ];

        $groups = [];

        foreach ($this->palette as $item) {
            $key = $item['category'];
            $groups[$key]['label'] ??= $labels[$key] ?? ucfirst($key);
            $groups[$key]['items'][] = $item;
        }

        return $groups;
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function styleTokens(): array
    {
        return FilamentPageBuilderPlugin::get()->getStyleTokens();
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array{id: string, label: string, type: string, children: array<int, mixed>}>
     */
    protected function structureFrom(array $nodes): array
    {
        return array_map(function (array $node): array {
            $children = [];

            foreach ($node['slotNames'] ?? [] as $slot) {
                array_push($children, ...($node['children'][$slot] ?? []));
            }

            return [
                'id' => $node['id'],
                'label' => $node['label'],
                'type' => $node['type'],
                'children' => $this->structureFrom($children),
            ];
        }, $nodes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decorateChildren(?string $parent, ?string $slot = null): array
    {
        return array_map(
            fn (array $block): array => $this->decorate($block, withChildren: true),
            BlockTree::childrenOf($this->blocks, $parent, $slot),
        );
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    protected function decorate(array $block, bool $withChildren): array
    {
        $definition = $this->registry()->find($block['type']);
        $slotNames = $this->registry()->slots($block['type'], $block['data'] ?? []);

        $decorated = [
            ...$block,
            'data' => $this->normaliser()->normaliseData(
                $block['data'] ?? [],
                $this->registry()->fileFields($block['type']),
            ),
            'view' => $definition === null ? null : $definition::view(),
            'label' => $definition === null ? $block['type'] : $definition::label(),
            'isKnown' => $definition !== null,
            'isEditable' => $this->registry()->isVisible($block['type']),
            'hasContent' => $this->blockHasContent($block),
            'isContainer' => $slotNames !== [],
            'slotNames' => $slotNames,
            'settings' => is_array($block['settings'] ?? null) ? $block['settings'] : [],
        ];

        if ($withChildren) {
            $children = [];

            foreach ($slotNames as $name) {
                $children[$name] = $this->decorateChildren($block['id'], $name);
            }

            $decorated['children'] = $children;
        }

        return $decorated;
    }

    /**
     * Whether a block holds anything a user would mind losing.
     *
     * A freshly dropped section already has a column count in `data`, which is not
     * content — only filled fields that differ from the type's defaults, or children
     * sitting in its slots, count. Prompting on an empty section would train the
     * editor to dismiss the confirm.
     *
     * @param  array<string, mixed>  $block
     */
    protected function blockHasContent(array $block): bool
    {
        if (BlockTree::childrenOf($this->blocks, $block['id'] ?? '') !== []) {
            return true;
        }

        $defaults = $this->registry()->defaults($block['type'] ?? null);

        foreach ($block['data'] ?? [] as $key => $value) {
            if (array_key_exists($key, $defaults) && $defaults[$key] === $value) {
                continue;
            }

            if ($this->hasContent($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a value holds anything a user would mind losing.
     *
     * Drives the delete confirmation: prompting before removing a block the editor has
     * only just dropped in and not yet filled would train them to dismiss the prompt.
     */
    protected function hasContent(mixed $data): bool
    {
        if (! is_array($data)) {
            return filled($data);
        }

        foreach ($data as $value) {
            if ($this->hasContent($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{type: string, label: string, icon: ?string, category: string, description: ?string}>
     */
    public function getPaletteProperty(): array
    {
        return array_values(array_map(
            fn (string $block): array => [
                'type' => $block::type(),
                'label' => $block::label(),
                'icon' => $block::icon(),
                'category' => $this->registry()->category($block::type()),
                'description' => $this->registry()->description($block::type()),
            ],
            $this->registry()->visible(),
        ));
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public function getSelectionPathProperty(): array
    {
        if ($this->selectedId === null) {
            return [];
        }

        $path = [];
        $id = $this->selectedId;
        $guard = 0;

        while ($id !== null && $guard++ < 20) {
            $block = BlockTree::find($this->blocks, $id);

            if ($block === null) {
                break;
            }

            $definition = $this->registry()->find($block['type']);
            $path[] = [
                'id' => $id,
                'label' => $definition === null ? $block['type'] : $definition::label(),
            ];
            $id = $block['parent'] ?? null;
        }

        return array_reverse($path);
    }

    /**
     * @return array<int, array{id: string, reason: string, parent: ?string, slot: ?string, label: string}>
     */
    public function getGhostsProperty(): array
    {
        $ghosts = BlockTree::ghosts(
            $this->blocks,
            fn (string $type, array $data): array => $this->registry()->slots($type, $data),
        );

        return array_map(function (array $ghost): array {
            $block = BlockTree::find($this->blocks, $ghost['id']);
            $definition = $block === null ? null : $this->registry()->find($block['type']);

            return [
                ...$ghost,
                'label' => $definition === null ? ($block['type'] ?? $ghost['id']) : $definition::label(),
            ];
        }, $ghosts);
    }

    public function hasNestedBlocks(): bool
    {
        foreach ($this->blocks as $block) {
            if (($block['parent'] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    /* ── Mutations ─────────────────────────────────────── */

    public function moveBlock(string $id, int $to, ?string $parent = null, ?string $slot = null): void
    {
        $parent = $this->nullableString($parent);
        $slot = $this->nullableString($slot);

        if (! $this->canPlace($parent, $slot)) {
            return;
        }

        $next = BlockTree::move($this->blocks, $id, $to, $parent, $slot);

        if ($next === $this->blocks) {
            return;
        }

        $this->remember();
        $this->blocks = $next;
        $this->syncDirty();
    }

    /**
     * Returns the new block's id, so the canvas can put the caret straight into it.
     */
    public function insertBlock(string $type, ?int $at = null, ?string $parent = null, ?string $slot = null): ?string
    {
        if (! $this->registry()->isVisible($type)) {
            return null;
        }

        $parent = $this->nullableString($parent);
        $slot = $this->nullableString($slot);

        // A click on the palette with something selected inserts into the first slot
        // of a container, or as the next sibling of a leaf.
        // An explicit drop still wins — it passes $at / $parent / $slot itself.
        if ($at === null && $parent === null && $this->selectedId !== null) {
            $selected = BlockTree::find($this->blocks, $this->selectedId);

            if ($selected !== null) {
                $slots = $this->registry()->slots($selected['type'], $selected['data'] ?? []);

                if ($slots !== []) {
                    $parent = $selected['id'];
                    $slot = $slots[0];
                    $at = count(BlockTree::childrenOf($this->blocks, $parent, $slot));
                } else {
                    $parent = $this->nullableString($selected['parent'] ?? null);
                    $slot = $this->nullableString($selected['slot'] ?? null);
                    $at = ($selected['position'] ?? 0) + 1;
                }
            }
        }

        if (! $this->canPlace($parent, $slot)) {
            return null;
        }

        $block = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => $this->registry()->defaults($type),
            'settings' => [],
        ];

        $next = BlockTree::insert($this->blocks, $block, $at, $parent, $slot);

        if ($next === $this->blocks) {
            return null;
        }

        $this->remember();
        $this->blocks = $next;
        $this->syncDirty();

        $this->selectBlock($block['id']);

        return $block['id'];
    }

    public function duplicateBlock(string $id): void
    {
        $source = BlockTree::find($this->blocks, $id);

        if ($source === null || ! $this->registry()->isVisible($source['type'])) {
            return;
        }

        foreach (BlockTree::descendantIds($this->blocks, $id) as $descendantId) {
            $descendant = BlockTree::find($this->blocks, $descendantId);

            if ($descendant !== null && ! $this->registry()->isVisible($descendant['type'])) {
                return;
            }
        }

        $next = BlockTree::duplicate($this->blocks, $id);

        if ($next === $this->blocks) {
            return;
        }

        $this->remember();
        $this->blocks = $next;
        $this->syncDirty();
    }

    public function removeBlock(string $id): void
    {
        if (BlockTree::find($this->blocks, $id) === null) {
            return;
        }

        $removed = [$id, ...BlockTree::descendantIds($this->blocks, $id)];
        $next = BlockTree::remove($this->blocks, $id);

        $this->remember();
        $this->blocks = $next;

        if (in_array($this->selectedId, $removed, true)) {
            $this->selectBlock(null);
        }

        $this->syncDirty();
    }

    /**
     * Move a ghost back onto a surface the outline and canvas walk.
     *
     * Orphans become roots. Hidden-slot children land in the parent's last
     * visible column so they reappear without inventing a slot.
     */
    public function revealGhost(string $id): void
    {
        $ghost = collect($this->ghosts)->firstWhere('id', $id);

        if ($ghost === null) {
            return;
        }

        if ($ghost['reason'] === 'orphan' || $ghost['parent'] === null) {
            $this->moveBlock($id, count(BlockTree::childrenOf($this->blocks, null)), null, null);

            return;
        }

        $parent = BlockTree::find($this->blocks, $ghost['parent']);

        if ($parent === null) {
            $this->moveBlock($id, count(BlockTree::childrenOf($this->blocks, null)), null, null);

            return;
        }

        $slots = $this->registry()->slots($parent['type'], $parent['data'] ?? []);

        if ($slots === []) {
            $this->moveBlock($id, count(BlockTree::childrenOf($this->blocks, null)), null, null);

            return;
        }

        $slot = $slots[array_key_last($slots)];
        $at = count(BlockTree::childrenOf($this->blocks, $parent['id'], $slot));

        $this->moveBlock($id, $at, $parent['id'], $slot);
    }

    /* ── Selection and the inspector ───────────────────── */

    public function selectBlock(?string $id): void
    {
        $this->commitSelectedBlock();
        $this->commitSelectedSettings();
        $this->commitSelectedStyle();
        $this->commitSelectedAnchor();

        $this->selectedId = $id;

        $this->syncInspector();
    }

    /**
     * Point the inspector at whatever the selected block now holds.
     *
     * Deliberately does not commit first. Undo replaces the block array while the
     * inspector still holds the state that was just undone, and committing it would
     * write that state straight back.
     */
    protected function syncInspector(): void
    {
        $index = $this->selectedId === null ? null : $this->indexOf($this->selectedId);

        if ($index === null) {
            $this->selectedId = null;
        }

        $this->blockData = $index === null ? [] : ($this->blocks[$index]['data'] ?? []);
        $this->blockSettings = $index === null ? [] : ($this->blocks[$index]['settings'] ?? []);
        $anchor = $index === null ? null : ($this->blocks[$index]['anchor'] ?? '');
        $this->blockAnchor = is_string($anchor) ? $anchor : '';

        // The schema is cached per request and built from $selectedId. Selecting a
        // different block mid-request leaves that cached schema pointing at the previous
        // block — or at no block at all — so it is dropped and rebuilt before the new
        // state is filled in. Without this the inspector hydrates an empty editor and
        // committing it would wipe the block's content.
        $this->cacheSchema('form', null);

        $this->form->fill($this->blockData);

        $this->fillStyleInspector();
    }

    /**
     * Refill the content inspector from the stored data after a change made elsewhere
     * (a drag, a resize), without committing what it held.
     */
    protected function refillContentInspector(): void
    {
        $index = $this->selectedId === null ? null : $this->indexOf($this->selectedId);

        $this->blockData = $index === null ? [] : ($this->blocks[$index]['data'] ?? []);

        $this->cacheSchema('form', null);

        $this->form->fill($this->blockData);
    }

    /**
     * What the inspector's header says about the selection: which element this is, and
     * the way back up to the block that holds it.
     *
     * @return array{id: string, type: string, label: string, icon: ?string, description: ?string, parentId: ?string, parentLabel: ?string, isContainer: bool}|null
     */
    public function getSelectedBlockProperty(): ?array
    {
        $block = $this->selectedId === null ? null : BlockTree::find($this->blocks, $this->selectedId);

        if ($block === null) {
            return null;
        }

        $definition = $this->registry()->find($block['type']);
        $parent = ($block['parent'] ?? null) === null ? null : BlockTree::find($this->blocks, $block['parent']);
        $parentDefinition = $parent === null ? null : $this->registry()->find($parent['type']);

        return [
            'id' => $block['id'],
            'type' => $block['type'],
            'label' => $definition === null ? $block['type'] : $definition::label(),
            'icon' => $definition === null ? null : $definition::icon(),
            'description' => $this->registry()->description($block['type']),
            'parentId' => $parent['id'] ?? null,
            'parentLabel' => $parent === null ? null : ($parentDefinition === null ? $parent['type'] : $parentDefinition::label()),
            'isContainer' => $this->registry()->isContainer($block['type']),
        ];
    }

    /**
     * Copy the inspector's state back onto the selected block.
     *
     * Uses getState() rather than raw state, because the two are different shapes: a
     * RichEditor holds a TipTap document while editing but stores HTML, and an upload
     * holds an array while storing a path. getState() also runs the dehydration hooks
     * that move an uploaded file out of temporary storage.
     *
     * getState() validates, and a block being filled in is often incomplete, so an
     * invalid block falls back to converting the raw state by hand. Declared file fields
     * keep their stored value in that path: an in-progress upload has no permanent path
     * yet, and writing its temporary URL would leave a dead link in the database.
     */
    public function commitSelectedBlock(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $index = $this->indexOf($this->selectedId);

        if ($index === null) {
            return;
        }

        // The inspector renders no fields for a block the user may not author, so the
        // form state is empty. Committing that would erase the block's content.
        if (! $this->registry()->isVisible($this->blocks[$index]['type'])) {
            return;
        }

        $stored = $this->blocks[$index]['data'] ?? [];

        try {
            $data = $this->form->getState();
        } catch (ValidationException) {
            $data = $this->normaliser()->normaliseData($this->blockData);

            foreach ($this->registry()->fileFields($this->blocks[$index]['type']) as $field) {
                $data[$field] = $stored[$field] ?? null;
            }
        }

        $data = $this->withoutNewEmptyFields($data, $stored);

        if ($data !== $stored) {
            $this->remember();

            $this->blocks[$index]['data'] = $data;
            $this->syncDirty();
        }
    }

    /**
     * Drop fields the stored block never had and the form only reports as empty.
     *
     * A block type that gains a field (a section's new "Space between") hands every
     * existing block a `null` for it, and a toggle a `false`. Committing those would make
     * the page dirty, and record an undo step, merely because an old block was selected.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    protected function withoutNewEmptyFields(array $data, array $stored): array
    {
        foreach ($data as $key => $value) {
            if (! array_key_exists($key, $stored) && ($value === null || $value === false || $value === '' || $value === [])) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Write one field of one block, from an edit made directly on the page.
     *
     * Everything here is checked rather than trusted. The canvas is a public Livewire
     * surface: the field has to be one the block itself declared editable, and the value
     * has to suit that kind. Marking an element up with `@editable` is not enough — the
     * block's own declaration is the authority, so no amount of markup can open a field
     * the block never offered.
     */
    public function setBlockField(string $id, string $field, mixed $value): void
    {
        $index = $this->indexOf($id);

        if ($index === null) {
            return;
        }

        $type = $this->blocks[$index]['type'];

        if (! $this->registry()->isVisible($type)) {
            return;
        }

        $editable = PageBuilder::editablesFor($type)[$field] ?? null;

        if ($editable === null || ! $editable->accepts($value)) {
            return;
        }

        // In-place rich text is not mounted yet. Accepting any string here would
        // bypass TipTap's allowlist and land raw markup in a `{!! !!}` field.
        if ($editable->kind === Editable::RICH_TEXT) {
            return;
        }

        if (($this->blocks[$index]['data'][$field] ?? null) === $value) {
            return;
        }

        $this->remember();

        $this->blocks[$index]['data'][$field] = $value;
        $this->syncDirty();

        // The inspector is a second view of the same field and would otherwise keep
        // showing what the page said before the edit.
        if ($this->selectedId === $id) {
            $this->blockData[$field] = $value;
            $this->cacheSchema('form', null);
            $this->form->fill($this->blockData);
        }
    }

    public function updatedBlockData(): void
    {
        $this->commitSelectedBlock();
    }

    public function updatedBlockSettings(): void
    {
        $this->commitSelectedSettings();
    }

    /**
     * Write the inspector's style tab back onto the selected block.
     *
     * Only names that appear in the configured token set are kept, so a crafted
     * Livewire payload cannot store `padding: 13px lime`.
     */
    public function commitSelectedSettings(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $index = $this->indexOf($this->selectedId);

        if ($index === null) {
            return;
        }

        $clean = [];

        foreach ($this->styleTokens() as $key => $options) {
            $allowed = $this->tokenValues($options);
            $value = $this->blockSettings[$key] ?? null;

            if (is_string($value) && in_array($value, $allowed, true)) {
                $clean[$key] = $value;
            }
        }

        if ($clean === ($this->blocks[$index]['settings'] ?? [])) {
            return;
        }

        $this->remember();
        $this->blocks[$index]['settings'] = $clean;
        $this->blockSettings = $clean;
        $this->syncDirty();
    }

    public function updatedBlockAnchor(): void
    {
        $this->commitSelectedAnchor();
    }

    public function commitSelectedAnchor(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $index = $this->indexOf($this->selectedId);

        if ($index === null) {
            return;
        }

        $value = trim($this->blockAnchor);
        $next = $value === '' ? null : $value;

        if ($next !== null && ! BlockTree::isValidAnchor($next)) {
            return;
        }

        $current = $this->blocks[$index]['anchor'] ?? null;
        $current = is_string($current) && $current !== '' ? $current : null;

        if ($current === $next) {
            return;
        }

        $this->remember();

        if ($next === null) {
            unset($this->blocks[$index]['anchor']);
        } else {
            $this->blocks[$index]['anchor'] = $next;
        }

        $this->syncDirty();
    }

    /**
     * @param  array<int|string, string>  $options
     * @return array<int, string>
     */
    protected function tokenValues(array $options): array
    {
        $values = array_is_list($options) ? $options : array_keys($options);

        return array_values(array_filter($values, fn (mixed $value): bool => is_string($value) || is_int($value)));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(fn (): array => $this->selectedBlockSchema())
            ->statePath('blockData');
    }

    /**
     * Fields for whichever block is selected, or none.
     *
     * @return array<int, mixed>
     */
    protected function selectedBlockSchema(): array
    {
        if (! $this->isSelectedBlockEditable()) {
            return [];
        }

        return $this->registry()->find($this->selectedBlockType())::schema();
    }

    /**
     * Whether the selected block is one the current user is allowed to author.
     *
     * A block can be present on a page and still be off limits to whoever opened it:
     * custom markup is the usual case. The block stays visible on the canvas and can be
     * moved or deleted, but its fields are withheld.
     */
    public function isSelectedBlockEditable(): bool
    {
        return $this->registry()->isVisible($this->selectedBlockType());
    }

    /**
     * Whether the selected block is a type this application still registers.
     *
     * Distinct from editability: a retired type cannot be edited by anyone, and telling
     * the editor they lack permission for it would be a lie.
     */
    public function isSelectedBlockKnown(): bool
    {
        return $this->selectedId === null || $this->registry()->has($this->selectedBlockType());
    }

    protected function selectedBlockType(): ?string
    {
        $index = $this->selectedId === null ? null : $this->indexOf($this->selectedId);

        return $index === null ? null : $this->blocks[$index]['type'];
    }

    /* ── History ───────────────────────────────────────── */

    public function undo(): void
    {
        $this->travel($this->history()->undo($this->blocks));
    }

    public function redo(): void
    {
        $this->travel($this->history()->redo($this->blocks));
    }

    public function getCanUndoProperty(): bool
    {
        return $this->history()->canUndo();
    }

    public function getCanRedoProperty(): bool
    {
        return $this->history()->canRedo();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     */
    protected function travel(?array $blocks): void
    {
        if ($blocks === null) {
            return;
        }

        $this->blocks = $blocks;
        $this->syncDirty();

        // The selected block may not exist in the state we travelled to.
        $this->syncInspector();
    }

    /* ── Persistence ───────────────────────────────────── */

    public function save(): void
    {
        $this->commitSelectedBlock();
        $this->commitSelectedSettings();
        $this->commitSelectedStyle();
        $this->commitSelectedAnchor();

        /** @var Model $record */
        $record = $this->getRecord();

        $record->refresh();

        if ($this->loadedUpdatedAt !== null
            && optional($record->updated_at)->toJSON() !== $this->loadedUpdatedAt) {
            Notification::make()
                ->title(__('page-builder::chrome.saved_elsewhere'))
                ->danger()
                ->send();

            return;
        }

        $record->{$this->blocksAttribute()} = $this->blocks;
        $record->save();

        $this->loadedUpdatedAt = optional($record->fresh()->updated_at)->toJSON();
        $this->markSaved();

        Notification::make()
            ->title(__('page-builder::chrome.layout_saved'))
            ->success()
            ->send();
    }

    protected function indexOf(string $id): ?int
    {
        return BlockTree::indexOf($this->blocks, $id);
    }

    protected function nullableString(?string $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * A parent of null is the page root. Anything else must be a container
     * that currently exposes `$slot`.
     */
    protected function canPlace(?string $parent, ?string $slot): bool
    {
        if ($parent === null) {
            return $slot === null;
        }

        $container = BlockTree::find($this->blocks, $parent);

        if ($container === null || ! $this->registry()->isContainer($container['type'])) {
            return false;
        }

        $slots = $this->registry()->slots($container['type'], $container['data'] ?? []);

        return $slot !== null && in_array($slot, $slots, true);
    }

    protected function markSaved(): void
    {
        $this->savedBlocks = $this->blocks;
        $this->isDirty = false;
    }

    protected function syncDirty(): void
    {
        $this->isDirty = $this->blocks !== $this->savedBlocks;
    }

    protected function rawHasDuplicateIds(mixed $blocks): bool
    {
        if (! is_array($blocks)) {
            return false;
        }

        $seen = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $id = $block['id'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            if (isset($seen[$id])) {
                return true;
            }

            $seen[$id] = true;
        }

        return false;
    }
}
