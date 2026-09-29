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

    public static function type(): string
    {
        return 'list';
    }

    public static function label(): string
    {
        return 'List';
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
        return 'Bulleted or numbered points, one per line.';
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
                ->label('Items')
                ->rows(6)
                ->helperText('One item per line.'),
            ToggleButtons::make('marker')
                ->label('Marker')
                ->options(self::MARKERS)
                ->default('bullet')
                ->inline()
                ->grouped(),
        ];
    }
}
