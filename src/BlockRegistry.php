<?php

namespace CarlJanzell\FilamentPageBuilder;

use CarlJanzell\FilamentPageBuilder\Contracts\Container;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use InvalidArgumentException;

/**
 * The single source of truth for which block types exist.
 *
 * Both editing surfaces and the public renderer resolve through this, so a block can
 * never mean one thing in the form and another on the canvas.
 */
class BlockRegistry
{
    /**
     * @var array<string, class-string<PageBlock>>
     */
    protected array $blocks = [];

    protected ?Shortcodes $shortcodes = null;

    /**
     * Hosts the application allows embeds from, on top of the shipped list.
     *
     * @var array<int, string>
     */
    protected array $embedHosts = [];

    /**
     * @param  array<int, class-string<PageBlock>>  $blocks
     */
    public function register(array $blocks): static
    {
        foreach ($blocks as $block) {
            if (! is_subclass_of($block, PageBlock::class)) {
                throw new InvalidArgumentException("[{$block}] must implement ".PageBlock::class.'.');
            }

            $this->blocks[$block::type()] = $block;
        }

        return $this;
    }

    /**
     * @return array<string, class-string<PageBlock>>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Only the blocks the current user may use.
     *
     * @return array<string, class-string<PageBlock>>
     */
    public function visible(): array
    {
        return array_filter($this->blocks, fn (string $block): bool => $block::isVisible());
    }

    /**
     * @return class-string<PageBlock>|null
     */
    public function find(?string $type): ?string
    {
        return $this->blocks[$type] ?? null;
    }

    public function has(?string $type): bool
    {
        return $this->find($type) !== null;
    }

    /**
     * Whether the current user may author blocks of this type.
     *
     * Distinct from has(): a type can be registered and still be off limits. Anything
     * that creates or edits block content has to ask this rather than has(), because
     * hiding a block from the palette is a presentation detail and the canvas mutations
     * are reachable directly over the wire.
     */
    public function isVisible(?string $type): bool
    {
        $block = $this->find($type);

        return $block !== null && $block::isVisible();
    }

    /**
     * Blade component for a type, or null when the type is unknown.
     *
     * Unknown types are expected rather than exceptional: content outlives schema changes,
     * so the renderer skips what it does not recognise instead of failing the page.
     */
    public function view(?string $type): ?string
    {
        $block = $this->find($type);

        return $block === null ? null : $block::view();
    }

    /**
     * @return array<int, string>
     */
    public function fileFields(?string $type): array
    {
        $block = $this->find($type);

        return $block === null ? [] : $block::fileFields();
    }

    /**
     * Whether this type can hold other blocks.
     */
    public function isContainer(?string $type): bool
    {
        $block = $this->find($type);

        return $block !== null && is_subclass_of($block, Container::class);
    }

    /**
     * Slot names a container currently exposes, or nothing for a leaf.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function slots(?string $type, array $data = []): array
    {
        $block = $this->find($type);

        if ($block === null || ! is_subclass_of($block, Container::class)) {
            return [];
        }

        return $block::slots($data);
    }

    /**
     * Starting data for a freshly dropped block, if it has any.
     *
     * @return array<string, mixed>
     */
    public function defaults(?string $type): array
    {
        $block = $this->find($type);

        if ($block !== null && method_exists($block, 'defaults')) {
            /** @var array<string, mixed> $defaults */
            $defaults = $block::defaults();

            return $defaults;
        }

        return [];
    }

    /**
     * Palette group for a type. App blocks that do not declare one land in "blocks".
     */
    public function category(?string $type): string
    {
        $block = $this->find($type);

        if ($block !== null && method_exists($block, 'category')) {
            $category = $block::category();

            return is_string($category) && $category !== '' ? $category : 'blocks';
        }

        return 'blocks';
    }

    /**
     * Optional one-line palette subtitle. Typos on the static are ignored.
     */
    public function description(?string $type): ?string
    {
        $block = $this->find($type);

        if ($block !== null && method_exists($block, 'description')) {
            $description = $block::description();

            return is_string($description) && $description !== '' ? $description : null;
        }

        return null;
    }

    /**
     * The shortcodes this panel's pages may call.
     *
     * Kept per registry, like the blocks themselves, so the public site resolves the same
     * set the canvas offered: both reach this through the current-or-default panel.
     */
    public function shortcodes(): Shortcodes
    {
        return $this->shortcodes ??= new Shortcodes;
    }

    /**
     * @param  array<int, string>  $hosts
     */
    public function allowEmbedHosts(array $hosts): static
    {
        foreach ($hosts as $host) {
            if (is_string($host) && preg_match('/^[a-z0-9.-]+$/i', $host)) {
                $this->embedHosts[] = strtolower($host);
            }
        }

        $this->embedHosts = array_values(array_unique($this->embedHosts));

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function embedHosts(): array
    {
        return $this->embedHosts;
    }

    /**
     * Where a block's resize handle writes, per axis, when it is not the style layer.
     *
     * By default the width handle sets the block's width and the height handle its
     * minimum height. A block whose size *is* its content — a spacer — maps an axis onto
     * one of its own data fields instead, through an optional static `resizable()`.
     *
     * @return array<string, string>
     */
    public function resizable(?string $type): array
    {
        $block = $this->find($type);

        if ($block !== null && method_exists($block, 'resizable')) {
            $map = $block::resizable();

            return is_array($map) ? array_filter($map, 'is_string') : [];
        }

        return [];
    }
}
