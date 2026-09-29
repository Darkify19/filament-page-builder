<?php

namespace CarlJanzell\FilamentPageBuilder\Filament\Pages\Concerns;

/**
 * The Preview button: the page as a visitor will see it, before it is saved.
 *
 * The canvas is an editor — outlines, drop wells, inert links, scripts held back — and a
 * responsive width that is only a narrower box. Preview renders the current unsaved
 * blocks through the public renderer into a real document, framed at a real width, so
 * media queries fire, links and accordions work and Custom code runs.
 */
trait PreviewsPage
{
    /**
     * The whole preview document, for the canvas to load into its preview frame.
     */
    public function previewDocument(): string
    {
        $this->commitSelectedBlock();
        $this->commitSelectedSettings();
        $this->commitSelectedStyle();
        $this->commitSelectedAnchor();

        return view('page-builder::preview', [
            'title' => $this->getRecordTitle(),
            'blocks' => $this->blocks,
            'stylesView' => $this->canvasStylesView(),
        ])->render();
    }
}
