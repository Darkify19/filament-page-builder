<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Throwable;

/**
 * Hand-written HTML, CSS and JavaScript: animations, embeds a provider hands out as a
 * script, a design pasted from elsewhere.
 *
 * Whatever is written here runs on the public page with the visitor's full trust, so the
 * block is withheld from every editor unless the application opts in with
 * `FilamentPageBuilderPlugin::allowCode()` and says who may use it. Existing code blocks
 * still render for everyone; only authoring is gated.
 *
 * The script does not run on the canvas. It would run again on every re-render, stacking
 * listeners and restarting animations under the editor's cursor, and a mistake in it
 * could break the editor itself. Preview runs it, exactly as the published page will.
 */
class CodeBlock implements PageBlock
{
    public static function type(): string
    {
        return 'code';
    }

    public static function label(): string
    {
        return 'Custom code';
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-code-bracket-square';
    }

    public static function category(): string
    {
        return 'developer';
    }

    public static function description(): string
    {
        return 'Your own HTML, CSS and JavaScript — for animations and custom designs.';
    }

    public static function view(): string
    {
        return 'page-builder::components.code';
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
        try {
            return FilamentPageBuilderPlugin::get()->canUseCode();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Tabs::make('Code')
                ->contained(false)
                ->tabs([
                    Tab::make('HTML')->schema([
                        CodeEditor::make('html')
                            ->hiddenLabel()
                            ->language(Language::Html),
                        Text::make('Shortcodes work here too, e.g. [year].'),
                    ]),
                    Tab::make('CSS')->schema([
                        CodeEditor::make('css')
                            ->hiddenLabel()
                            ->language(Language::Css),
                        Text::make('Applies to the whole page. Start your selectors with a class of your own.'),
                    ]),
                    Tab::make('JavaScript')->schema([
                        CodeEditor::make('js')
                            ->hiddenLabel()
                            ->language(Language::JavaScript),
                        Text::make('Runs once the page has loaded, in Preview and on the published page — not on the canvas. `root` is this block\'s element.'),
                    ]),
                ]),
        ];
    }

    /**
     * The script as it can be printed inside a `<script>` element.
     *
     * A literal `</script>` in the author's code would end the element early and spill
     * the rest into the page as markup; `<!--` switches the HTML parser into a mode that
     * can swallow the closing tag. Both are rewritten into forms JavaScript reads the same.
     */
    public static function script(string $js): string
    {
        $js = preg_replace('#</(script)#i', '<\\/$1', $js) ?? '';

        return str_replace('<!--', '<\\!--', $js);
    }

    public static function stylesheet(string $css): string
    {
        return preg_replace('#</(style)#i', '<\\/$1', $css) ?? '';
    }
}
