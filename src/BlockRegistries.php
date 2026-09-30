<?php

namespace CarlJanzell\FilamentPageBuilder;

use Filament\Facades\Filament;

/**
 * One registry per panel.
 *
 * A single shared registry merges the block sets of every panel that registers the
 * plugin, so an admin panel and, say, a departmental panel would each offer the other's
 * blocks. Registration is a per-panel act; the registry has to be too.
 */
class BlockRegistries
{
    /**
     * @var array<string, BlockRegistry>
     */
    protected array $registries = [];

    /**
     * The panel whose blocks a public render asked for, while it runs.
     */
    protected ?string $using = null;

    public function for(string $panel): BlockRegistry
    {
        return $this->registries[$panel] ??= new BlockRegistry;
    }

    /**
     * The registry belonging to whichever panel is being served.
     *
     * Falls back to the default panel: block content is also rendered on the public site,
     * where no panel is current but the block types are still the application's. A render
     * that named its panel (see using()) gets that panel's instead.
     */
    public function current(): BlockRegistry
    {
        if ($this->using !== null) {
            return $this->for($this->using);
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        return $this->for($panel?->getId() ?? 'default');
    }

    /**
     * Run a render with one panel's blocks, whichever panel is being served.
     *
     * The public site has no current panel, so it falls back to the default one. When the
     * plugin lives on another panel, say an admin panel beside a default app panel, every
     * block would be unknown to that fallback and the page would render empty.
     *
     * @template T
     *
     * @param  callable(): T  $render
     * @return T
     */
    public function using(?string $panel, callable $render): mixed
    {
        if ($panel === null) {
            return $render();
        }

        // Panels register their plugins lazily; resolving the panel fills its registry,
        // and an id that names no panel fails here rather than rendering nothing.
        Filament::getPanel($panel);

        $previous = $this->using;
        $this->using = $panel;

        try {
            return $render();
        } finally {
            $this->using = $previous;
        }
    }
}
