<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Set;

/**
 * Empty vertical space, to push one block away from the next.
 *
 * It was the block editors asked about most ("what is the spacer?"): on the canvas it
 * now shows itself as a hatched band with its height written on it, and its bottom edge
 * drags to size, the way an editor expects a gap to behave.
 */
class SpacerBlock implements PageBlock
{
    /**
     * @var array<string, int>
     */
    public const PRESETS = ['sm' => 16, 'md' => 32, 'lg' => 64, 'xl' => 96];

    /**
     * The preset heights as labels, translated.
     *
     * The same four keys as PRESETS, so which one a block stores is unaffected by the
     * locale it was authored in. Only the words move.
     *
     * @return array<string, string>
     */
    public static function heights(): array
    {
        return [
            'sm' => __('page-builder::blocks.common.small'),
            'md' => __('page-builder::blocks.common.medium'),
            'lg' => __('page-builder::blocks.common.large'),
            'xl' => __('page-builder::blocks.common.extra_large'),
        ];
    }

    public static function type(): string
    {
        return 'spacer';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.spacer.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public static function category(): string
    {
        return 'design';
    }

    public static function description(): string
    {
        return __('page-builder::blocks.spacer.description');
    }

    public static function view(): string
    {
        return 'page-builder::components.spacer';
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
        return ['height' => 'md'];
    }

    /**
     * The height handle writes the exact size, in pixels, into `size`.
     *
     * @return array<string, string>
     */
    public static function resizable(): array
    {
        return ['height' => 'size'];
    }

    /**
     * The spacer's height in pixels: an exact size wins over the preset.
     *
     * @param  array<string, mixed>  $data
     */
    public static function pixels(array $data): int
    {
        if (is_numeric($data['size'] ?? null) && (int) $data['size'] > 0) {
            return min(2000, (int) $data['size']);
        }

        return self::PRESETS[$data['height'] ?? 'md'] ?? self::PRESETS['md'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            ToggleButtons::make('height')
                ->label(__('page-builder::blocks.spacer.height'))
                ->options(self::heights())
                ->default('md')
                ->inline()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('size', null)),
            TextInput::make('size')
                ->label(__('page-builder::blocks.spacer.size'))
                ->numeric()
                ->minValue(1)
                ->maxValue(2000)
                ->suffix('px')
                ->placeholder(__('page-builder::blocks.spacer.size_placeholder'))
                ->helperText(__('page-builder::blocks.spacer.size_hint')),
        ];
    }
}
