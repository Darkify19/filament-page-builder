<?php

namespace CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns;

use CarlJanzell\FilamentPageBuilder\Filament\StyleSchema;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
use Filament\Forms\Form;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The custom style layer on the canvas: the Style and Layout tabs, the alignment buttons
 * on a selected block, and copying one block's look onto another.
 *
 * Follows the content inspector's rules exactly, for the same reasons: commit through
 * `getState()` (uploads move out of temporary storage there), rebuild the cached form
 * before filling it, never commit while syncing, record history only once something
 * will change. What reaches `$blocks` has always been through `BlockStyle::sanitize()`.
 */
trait EditsBlockStyle
{
    /**
     * The Style tab's state (how the selected block looks), for the selected block only.
     *
     * @var array<string, mixed>
     */
    public array $blockStyle = [];

    /**
     * The Layout tab's state (spacing, size, position) for the selected block only.
     *
     * @var array<string, mixed>
     */
    public array $blockLayout = [];

    public function styleForm(Form $form): Form
    {
        return $form
            ->schema(fn (): array => $this->showsStyleForms() ? StyleSchema::style() : [])
            ->statePath('blockStyle');
    }

    public function layoutForm(Form $form): Form
    {
        return $form
            ->schema(fn (): array => $this->showsStyleForms() ? StyleSchema::layout() : [])
            ->statePath('blockLayout');
    }

    protected function showsStyleForms(): bool
    {
        return $this->hasCustomStyles() && $this->selectedId !== null && $this->isSelectedBlockEditable();
    }

    public function hasCustomStyles(): bool
    {
        try {
            return FilamentPageBuilderPlugin::get()->hasCustomStyles();
        } catch (Throwable) {
            return true;
        }
    }

    public function updatedBlockStyle(): void
    {
        $this->commitSelectedStyle();
    }

    public function updatedBlockLayout(): void
    {
        $this->commitSelectedStyle();
    }

    /**
     * Copy the style tabs back onto the selected block.
     */
    public function commitSelectedStyle(): void
    {
        if ($this->selectedId === null || ! $this->hasCustomStyles()) {
            return;
        }

        $index = $this->indexOf($this->selectedId);

        if ($index === null || ! $this->registry()->isVisible($this->blocks[$index]['type'])) {
            return;
        }

        $stored = is_array($this->blocks[$index]['style'] ?? null) ? $this->blocks[$index]['style'] : [];

        try {
            $look = $this->styleForm->getState();
        } catch (ValidationException) {
            $look = $this->blockStyle;

            // An upload still in progress has only a temporary URL; keep what is stored.
            foreach (BlockStyle::FILE_FIELDS as $field) {
                $look[$field] = $stored[$field] ?? null;
            }
        }

        try {
            $layout = $this->layoutForm->getState();
        } catch (ValidationException) {
            $layout = $this->blockLayout;
        }

        [$look] = BlockStyle::split(is_array($look) ? $look : []);
        [, $layout] = BlockStyle::split(is_array($layout) ? $layout : []);
        $state = [...$look, ...$layout];

        foreach (['video_start', 'video_end'] as $field) {
            if (isset($state[$field]) && ! is_numeric($state[$field])) {
                $state[$field] = EmbedUrl::seconds($state[$field]);
            }
        }

        $this->writeStyle($index, BlockStyle::sanitize($state), refreshInspector: false);
    }

    /**
     * Set one style value on one block — the alignment buttons on the block's bar.
     *
     * An empty value clears the key, which is how pressing the active button turns
     * alignment back off.
     */
    public function setBlockStyle(string $id, string $key, mixed $value = null): void
    {
        $index = $this->indexOf($id);
        $kind = BlockStyle::fields()[$key] ?? null;

        if ($index === null || $kind === null || ! $this->registry()->isVisible($this->blocks[$index]['type'])) {
            return;
        }

        $this->commitSelectedStyle();

        $style = is_array($this->blocks[$index]['style'] ?? null) ? $this->blocks[$index]['style'] : [];
        $clean = BlockStyle::clean($value, $kind);

        if ($clean === null) {
            unset($style[$key]);
        } else {
            $style[$key] = $clean;
        }

        $this->writeStyle($index, BlockStyle::sanitize($style));
    }

    public function resetBlockStyle(string $id): void
    {
        $index = $this->indexOf($id);

        if ($index === null || ! $this->registry()->isVisible($this->blocks[$index]['type'])) {
            return;
        }

        $next = $this->blocks[$index];
        unset($next['style']);
        $next['settings'] = [];

        if ($next === $this->blocks[$index]) {
            return;
        }

        $this->remember();
        $this->blocks[$index] = $next;
        $this->syncDirty();

        if ($this->selectedId === $id) {
            $this->syncInspector();
        }
    }

    /**
     * A block's look, for the clipboard: its custom style and its token presets.
     *
     * @return array{style: array<string, mixed>, settings: array<string, mixed>}
     */
    public function copyBlockStyle(string $id): array
    {
        $block = $this->blocks[$this->indexOf($id) ?? -1] ?? null;

        return [
            'style' => BlockStyle::sanitize($block['style'] ?? []),
            'settings' => $this->cleanSettings($block['settings'] ?? []),
        ];
    }

    /**
     * Apply a copied look to a block. Both halves are checked as if they had been typed.
     *
     * @param  array<string, mixed>  $payload
     */
    public function pasteBlockStyle(string $id, array $payload): void
    {
        $index = $this->indexOf($id);

        if ($index === null || ! $this->registry()->isVisible($this->blocks[$index]['type'])) {
            return;
        }

        $this->commitSelectedStyle();

        $next = $this->blocks[$index];
        $style = BlockStyle::sanitize($payload['style'] ?? []);
        $next['settings'] = $this->cleanSettings($payload['settings'] ?? []);

        if ($style === []) {
            unset($next['style']);
        } else {
            $next['style'] = $style;
        }

        if ($next === $this->blocks[$index]) {
            return;
        }

        $this->remember();
        $this->blocks[$index] = $next;
        $this->syncDirty();

        if ($this->selectedId === $id) {
            $this->syncInspector();
        }
    }

    /**
     * @param  array<string, mixed>  $style  already sanitised
     */
    protected function writeStyle(int $index, array $style, bool $refreshInspector = true): void
    {
        $stored = is_array($this->blocks[$index]['style'] ?? null) ? $this->blocks[$index]['style'] : [];

        if ($style === $stored) {
            return;
        }

        $this->remember();

        if ($style === []) {
            unset($this->blocks[$index]['style']);
        } else {
            $this->blocks[$index]['style'] = $style;
        }

        $this->syncDirty();

        if ($refreshInspector && $this->selectedId === ($this->blocks[$index]['id'] ?? null)) {
            $this->fillStyleInspector();
        }
    }

    /**
     * Point the style tabs at the selected block. Never commits (see syncInspector()).
     */
    protected function fillStyleInspector(): void
    {
        $index = $this->selectedId === null ? null : $this->indexOf($this->selectedId);
        $style = $index === null ? [] : ($this->blocks[$index]['style'] ?? []);

        [$this->blockStyle, $this->blockLayout] = BlockStyle::split(is_array($style) ? $style : []);

        $this->rebuildForm('styleForm');
        $this->rebuildForm('layoutForm');

        $this->styleForm->fill($this->blockStyle);
        $this->layoutForm->fill($this->blockLayout);
    }

    /**
     * Only token names and values the panel configured, as the Style tab would store.
     *
     * @return array<string, string>
     */
    protected function cleanSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        $clean = [];

        foreach ($this->styleTokens() as $key => $options) {
            $value = $settings[$key] ?? null;

            if (is_string($value) && in_array($value, $this->tokenValues($options), true)) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
