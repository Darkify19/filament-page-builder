<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;

/**
 * Bulleted, numbered or ticked points, one per line.
 */
class ListBlock implements PageBlock
{
    /**
     * @var array<string, string>
     */
    public const MARKERS = [
        'bullet' => 'Bullets',
        'number' => 'Numbers',
        'check' => 'Ticks',
        'none' => 'None',
    ];

    /**
     * MARKERS, translated.
     *
     * The constant stays English because a constant cannot call `__()`, and it is public
     * API — something may read the keys, or a test may assert on them. Anything that
     * *shows* the options to an editor goes through here instead.
     *
     * @return array<string, string>
     */
    public static function markers(): array
    {
        return [
            'bullet' => __('page-builder::blocks.list.bullets'),
            'number' => __('page-builder::blocks.list.numbers'),
            'check' => __('page-builder::blocks.list.ticks'),
            'none' => __('page-builder::blocks.common.none'),
        ];
    }

    public static function type(): string
    {
        return 'list';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.list.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-list-bullet';
    }

    public static function category(): string
    {
        return 'content';
    }

    public static function description(): string
    {
        return __('page-builder::blocks.list.description');
    }

    public static function view(): string
    {
        return 'page-builder::components.list';
    }

    /**
     * @return array<int, string>
     */
    public static function fileFields(): array
    {
        return [];
    }

    public static function isVisible(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return ['marker' => 'bullet'];
    }

    /**
     * The non-empty lines of the stored text.
     *
     * @return array<int, string>
     */
    public static function itemsFrom(mixed $items): array
    {
        if (! is_string($items)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', $items) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Textarea::make('items')
                ->label(__('page-builder::blocks.list.items'))
                ->rows(6)
                ->helperText(__('page-builder::blocks.list.items_hint')),
            ToggleButtons::make('marker')
                ->label(__('page-builder::blocks.list.marker'))
                ->options(self::markers())
                ->default('bullet')
                ->inline()
                ->grouped(),
        ];
    }
}
