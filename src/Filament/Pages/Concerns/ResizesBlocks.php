<?php

namespace CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns;

use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;

/**
 * Dragging a block's edges, and the edge between two of a section's columns.
 *
 * The canvas script previews a drag locally and sends one call when the pointer is let
 * go, so a resize is one history step however far the edge travelled.
 */
trait ResizesBlocks
{
    /**
     * Set a block's width (percent of its column) or height (pixels) from a drag.
     *
     * By default the width lands in the style layer's `width` and the height in its
     * `min_height`. A block can map an axis onto one of its own fields instead with a
     * static `resizable()` — a spacer's height is the spacer. Null clears the size.
     */
    public function resizeBlock(string $id, string $axis, mixed $value = null): void
    {
        $index = $this->indexOf($id);

        if ($index === null || ! in_array($axis, ['width', 'height'], true)) {
            return;
        }

        $type = $this->blocks[$index]['type'];

        if (! $this->registry()->isVisible($type)) {
            return;
        }

        $field = $this->registry()->resizable($type)[$axis] ?? null;
        $amount = $value === null || $value === '' ? null : BlockStyle::number($value, $axis === 'width' ? 5 : 1, $axis === 'width' ? 100 : 4000);

        if ($field !== null) {
            $this->resizeIntoData($index, $field, $amount === null ? null : (int) round($amount));

            return;
        }

        $this->commitSelectedStyle();

        $style = is_array($this->blocks[$index]['style'] ?? null) ? $this->blocks[$index]['style'] : [];

        if ($axis === 'width') {
            unset($style['width'], $style['width_unit']);

            if ($amount !== null && $amount < 100) {
                $style['width'] = $amount;
                $style['width_unit'] = '%';
            }
        } else {
            unset($style['min_height'], $style['min_height_unit']);

            if ($amount !== null) {
                $style['min_height'] = (int) round($amount);
                $style['min_height_unit'] = 'px';
            }
        }

        $this->writeStyle($index, BlockStyle::sanitize($style));
    }

    /**
     * Set a section's column widths from a drag of the edge between two columns.
     *
     * The widths arrive as percentages and are stored as the section's ratio, so the
     * inspector's Column layout shows "Custom" and the public page draws the same tracks.
     *
     * @param  array<int, mixed>  $percentages
     */
    public function resizeColumns(string $id, array $percentages): void
    {
        $index = $this->indexOf($id);

        if ($index === null) {
            return;
        }

        $block = $this->blocks[$index];

        if (! $this->registry()->isVisible($block['type']) || ! $this->registry()->isContainer($block['type'])) {
            return;
        }

        $columns = count($this->registry()->slots($block['type'], $block['data'] ?? []));
        $widths = array_map(fn (mixed $width): int => (int) round((float) (is_numeric($width) ? $width : 0)), array_values($percentages));

        if ($columns < 2 || count($widths) !== $columns || min($widths) < 5) {
            return;
        }

        $ratio = implode('-', $widths);

        // A drag that lands on a preset stores the preset's own name.
        foreach (array_keys(SectionBlock::ratiosFor($columns)) as $preset) {
            if (SectionBlock::percentagesFor($columns, $preset) === SectionBlock::percentagesFor($columns, $ratio)) {
                $ratio = $preset;
            }
        }

        if (($block['data']['ratio'] ?? null) === $ratio) {
            return;
        }

        $this->commitSelectedBlock();

        $this->remember();
        $this->blocks[$index]['data']['ratio'] = $ratio;
        $this->syncDirty();

        if ($this->selectedId === $id) {
            $this->refillContentInspector();
        }
    }

    protected function resizeIntoData(int $index, string $field, ?int $value): void
    {
        $this->commitSelectedBlock();

        $current = $this->blocks[$index]['data'][$field] ?? null;

        if ($current === $value) {
            return;
        }

        $this->remember();

        if ($value === null) {
            unset($this->blocks[$index]['data'][$field]);
        } else {
            $this->blocks[$index]['data'][$field] = $value;
        }

        $this->syncDirty();

        if ($this->selectedId === $this->blocks[$index]['id']) {
            $this->refillContentInspector();
        }
    }
}
