<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Editable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

/**
 * A title, H1 to H6, typed straight onto the page.
 */
class HeadingBlock implements InlineEditable, PageBlock
{
    /**
     * @var array<int, string>
     */
    public const LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * @return array<string, Editable>
     */
    public static function editables(): array
    {
        return [
            'text' => Editable::text()->placeholder(__('page-builder::blocks.heading.placeholder')),
        ];
    }

    public static function type(): string
    {
        return 'heading';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.heading.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-h1';
    }

    public static function category(): string
    {
        return 'content';
    }

    public static function description(): string
    {
        return __('page-builder::blocks.heading.description');
    }

    public static function view(): string
    {
        return 'page-builder::components.heading';
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
        return ['level' => 'h2'];
    }

    public static function levelFor(mixed $level): string
    {
        return is_string($level) && in_array($level, self::LEVELS, true) ? $level : 'h2';
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('page-builder::blocks.heading.text'))
                ->maxLength(255),
            ToggleButtons::make('level')
                ->label(__('page-builder::blocks.heading.level'))
                ->options(array_combine(self::LEVELS, array_map('strtoupper', self::LEVELS)))
                ->default('h2')
                ->inline()
                ->grouped(),
        ];
    }
}
