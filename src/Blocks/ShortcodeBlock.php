<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\PageBuilder;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Text;
use Illuminate\Support\HtmlString;

/**
 * Live content from the application, through a registered shortcode.
 */
class ShortcodeBlock implements PageBlock
{
    public static function type(): string
    {
        return 'shortcode';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.shortcode.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-bolt';
    }

    public static function category(): string
    {
        return 'developer';
    }

    public static function description(): string
    {
        return __('page-builder::blocks.shortcode.description');
    }

    public static function view(): string
    {
        return 'page-builder::components.shortcode';
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
            Textarea::make('code')
                ->label(__('page-builder::blocks.shortcode.code'))
                ->rows(3)
                ->placeholder('[year]')
                ->helperText(__('page-builder::blocks.shortcode.code_hint')),
            Text::make(fn (): HtmlString => self::reference()),
        ];
    }

    /**
     * The shortcodes this site offers, for the inspector.
     */
    public static function reference(): HtmlString
    {
        $codes = PageBuilder::shortcodes()->all();

        if ($codes === []) {
            return new HtmlString('<span class="fpb-shortcode-ref">'.__('page-builder::blocks.shortcode.reference_empty').'</span>');
        }

        $items = collect($codes)->map(fn (array $code, string $name): string => '<li><code>'.e($code['example'] ?? "[{$name}]").'</code>'
            .(filled($code['description']) ? ' <span>'.e($code['description']).'</span>' : '').'</li>')->implode('');

        return new HtmlString('<div class="fpb-shortcode-ref"><strong>'.e(__('page-builder::blocks.shortcode.reference_title')).'</strong><ul>'.$items.'</ul></div>');
    }
}
