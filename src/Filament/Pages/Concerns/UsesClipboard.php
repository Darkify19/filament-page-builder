<?php

namespace CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns;

use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use CarlJanzell\FilamentPageBuilder\Support\BlockTree;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Copy and paste: blocks between pages, and designs pasted in from elsewhere.
 *
 * A copied block travels as JSON through the system clipboard (and the canvas's own
 * buffer, for browsers that will not let a page read it), so it can be pasted into
 * another page, another tab, even another site running the package. That makes a paste
 * untrusted input like any other call: every type must be one this user may author,
 * ids are minted fresh, and styles and tokens are cleaned exactly as if typed.
 */
trait UsesClipboard
{
    /**
     * Largest clipboard payload accepted, in bytes, and the most blocks one paste adds.
     */
    protected int $clipboardLimit = 524288;

    protected int $clipboardBlockLimit = 200;

    /**
     * A block and everything inside it, as the canvas's clipboard format.
     */
    public function copyBlocks(string $id): ?string
    {
        if (BlockTree::find($this->blocks, $id) === null) {
            return null;
        }

        $this->commitSelectedBlock();
        $this->commitSelectedStyle();

        $ids = [$id, ...BlockTree::descendantIds($this->blocks, $id)];
        $subtree = array_values(array_filter(
            $this->blocks,
            fn (array $block): bool => in_array($block['id'] ?? null, $ids, true),
        ));

        return json_encode(['fpb' => 1, 'blocks' => $subtree], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null;
    }

    /**
     * Paste copied blocks after `$targetId`, inside it (`$placement = 'inside'`), or at
     * the end of the page. Returns the first pasted id, or null when nothing was pasted.
     */
    public function pasteBlocks(string $payload, ?string $targetId = null, string $placement = 'after'): ?string
    {
        if (strlen($payload) > $this->clipboardLimit) {
            return $this->refusePaste(__('page-builder::chrome.paste_too_much'));
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded) || ($decoded['fpb'] ?? null) !== 1 || ! is_array($decoded['blocks'] ?? null)) {
            return $this->pasteText($payload, $targetId);
        }

        $incoming = BlockTree::hydrate($decoded['blocks']);

        if ($incoming === [] || count($incoming) > $this->clipboardBlockLimit) {
            return $this->refusePaste(__('page-builder::chrome.paste_nothing_usable'));
        }

        foreach ($incoming as $block) {
            if (! $this->registry()->isVisible($block['type'])) {
                $definition = $this->registry()->find($block['type']);
                $label = $definition === null ? $block['type'] : $definition::label();

                return $this->refusePaste(__('page-builder::chrome.paste_not_allowed', ['label' => $label]));
            }
        }

        [$parent, $slot, $at] = $this->placementFor($targetId, $placement);

        if (! $this->canPlace($parent, $slot)) {
            return $this->refusePaste(__('page-builder::chrome.paste_not_there'));
        }

        $map = [];

        foreach ($incoming as $block) {
            $map[$block['id']] = (string) Str::uuid();
        }

        $roots = [];
        $children = [];

        foreach ($incoming as $block) {
            $copy = [
                ...$block,
                'id' => $map[$block['id']],
                'settings' => $this->cleanSettings($block['settings'] ?? []),
            ];

            $style = BlockStyle::sanitize($block['style'] ?? []);
            unset($copy['style']);

            if ($style !== []) {
                $copy['style'] = $style;
            }

            if (! BlockTree::isValidAnchor($copy['anchor'] ?? null)) {
                unset($copy['anchor']);
            }

            $parentId = $block['parent'] ?? null;

            if (is_string($parentId) && isset($map[$parentId])) {
                $copy['parent'] = $map[$parentId];
                $children[] = $copy;
            } else {
                $roots[] = $copy;
            }
        }

        $height = 0;

        foreach ($roots as $root) {
            $height = max($height, BlockTree::subtreeHeight([...$roots, ...$children], $root['id']));
        }

        if (($parent === null ? 0 : BlockTree::depthOf($this->blocks, $parent)) + $height > BlockTree::MAX_DEPTH) {
            return $this->refusePaste(__('page-builder::chrome.paste_too_deep'));
        }

        $next = $this->blocks;

        foreach ($roots as $offset => $root) {
            $next = BlockTree::insert($next, $root, $at === null ? null : $at + $offset, $parent, $slot);
        }

        $next = BlockTree::flatten(BlockTree::reindex([...$next, ...$children]));

        $this->remember();
        $this->blocks = $next;
        $this->syncDirty();

        $this->selectBlock($roots[0]['id']);

        return $roots[0]['id'];
    }

    /**
     * Paste something that is not a copied block.
     *
     * Code copied out of an editor (HTML, a design from CodePen) becomes a Custom code
     * block for the people allowed to write one. Anything else becomes a text block: text
     * editing on the canvas is plain text, and a heap of pasted markup in it would only
     * show as tags.
     */
    public function pasteText(string $text, ?string $targetId = null): ?string
    {
        $text = trim(str_replace("\r\n", "\n", $text));

        if ($text === '') {
            return null;
        }

        if (strlen($text) > $this->clipboardLimit) {
            return $this->refusePaste(__('page-builder::chrome.paste_too_much'));
        }

        $isMarkup = (bool) preg_match('/^<(?:!doctype|html|head|body|div|section|style|script|svg|main|header|footer|article|nav|span|a|p|h[1-6]|ul|ol|img|button|form|table|canvas|link)\b/i', $text)
            && str_contains($text, '>');

        if ($isMarkup) {
            if (! $this->registry()->isVisible('code')) {
                return $this->refusePaste('Pasting HTML makes a Custom code block, which you are not allowed to add. Ask a developer.');
            }

            $data = $this->splitMarkup($text);
            $type = 'code';
        } else {
            if (! $this->registry()->isVisible('text')) {
                return null;
            }

            $data = ['body' => Str::limit($text, 20000, '')];
            $type = 'text';
        }

        [$parent, $slot, $at] = $this->placementFor($targetId, 'after');

        if (! $this->canPlace($parent, $slot)) {
            return null;
        }

        $block = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => $data,
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

    /**
     * Pull `<style>` and inline `<script>` out of pasted markup into their own tabs.
     *
     * @return array{html: string, css: string, js: string}
     */
    protected function splitMarkup(string $markup): array
    {
        $css = [];
        $js = [];

        $markup = preg_replace_callback('#<style\b[^>]*>(.*?)</style\s*>#is', function (array $match) use (&$css): string {
            $css[] = trim($match[1]);

            return '';
        }, $markup) ?? $markup;

        $markup = preg_replace_callback('#<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script\s*>#is', function (array $match) use (&$js): string {
            $js[] = trim($match[1]);

            return '';
        }, $markup) ?? $markup;

        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $markup, $body)) {
            $markup = $body[1];
        }

        return [
            'html' => trim($markup),
            'css' => implode("\n\n", array_filter($css)),
            'js' => implode("\n\n", array_filter($js)),
        ];
    }

    /**
     * Where something pasted or inserted "at" a block lands.
     *
     * `inside` a container means the end of its first column; `after` anything means the
     * next sibling. No target means the end of the page.
     *
     * @return array{0: ?string, 1: ?string, 2: ?int}
     */
    protected function placementFor(?string $targetId, string $placement): array
    {
        $target = $targetId === null ? null : BlockTree::find($this->blocks, $targetId);

        if ($target === null) {
            return [null, null, null];
        }

        $slots = $this->registry()->slots($target['type'], $target['data'] ?? []);

        if ($placement === 'inside' && $slots !== []) {
            return [$target['id'], $slots[0], count(BlockTree::childrenOf($this->blocks, $target['id'], $slots[0]))];
        }

        return [
            $this->nullableString($target['parent'] ?? null),
            $this->nullableString($target['slot'] ?? null),
            ($target['position'] ?? 0) + 1,
        ];
    }

    protected function refusePaste(string $message): null
    {
        Notification::make()->title($message)->warning()->send();

        return null;
    }
}
