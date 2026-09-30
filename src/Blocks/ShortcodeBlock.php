<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\PageBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
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
        return 'Shortcode';
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
        return 'Live data from the site, e.g. [year] or your own shortcodes.';
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
                ->label('Shortcode')
                ->rows(3)
                ->placeholder('[year]')
                ->helperText('Text around the shortcode is shown as written.'),
            Placeholder::make('shortcodes')->hiddenLabel()->content(fn (): HtmlString => self::reference()),
        ];
    }

    /**
     * The shortcodes this site offers, for the inspector.
     */
    public static function reference(): HtmlString
    {
        $codes = PageBuilder::shortcodes()->all();

        if ($codes === []) {
            return new HtmlString('<span class="fpb-shortcode-ref">This site has no shortcodes yet. A developer can add them with <code>FilamentPageBuilderPlugin::shortcode()</code>.</span>');
        }

        $items = collect($codes)->map(fn (array $code, string $name): string => '<li><code>'.e($code['example'] ?? "[{$name}]").'</code>'
            .(filled($code['description']) ? ' <span>'.e($code['description']).'</span>' : '').'</li>')->implode('');

        return new HtmlString('<div class="fpb-shortcode-ref"><strong>Available shortcodes</strong><ul>'.$items.'</ul></div>');
    }
}
