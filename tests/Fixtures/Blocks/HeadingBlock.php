<?php

namespace CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Editable;
use Filament\Forms\Components\TextInput;

class HeadingBlock implements InlineEditable, PageBlock
{
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
        // A stand-in for the package's own heading block, so it reads its copy from the same
        // place. An application shipping its own block translates it the same way; leaving
        // this hardcoded would mean the canvas is the one surface the tests never exercise in
        // another language.
        return __('page-builder::blocks.heading.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-bars-3';
    }

    public static function view(): string
    {
        return 'blocks.heading';
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
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            TextInput::make('text')->maxLength(255),
        ];
    }
}
