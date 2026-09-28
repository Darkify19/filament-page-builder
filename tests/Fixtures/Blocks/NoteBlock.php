<?php

namespace CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Editable;
use Filament\Forms\Components\RichEditor;

/**
 * A fixture that declares a rich-text field so the canvas can refuse inline writes to it.
 */
class NoteBlock implements InlineEditable, PageBlock
{
    /**
     * @return array<string, Editable>
     */
    public static function editables(): array
    {
        return [
            'body' => Editable::richText(),
        ];
    }

    public static function type(): string
    {
        return 'note';
    }

    public static function label(): string
    {
        return 'Note';
    }

    public static function icon(): ?string
    {
        return null;
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
            RichEditor::make('body'),
        ];
    }
}
