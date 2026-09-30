# Filament Page Builder

A drag-and-drop visual page builder for [Filament](https://filamentphp.com), storing page
content as an ordered array of typed blocks in a single JSON column.

<div class="filament-hidden">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/carljanzell/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/carljanzell/filament-page-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/carljanzell/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/carljanzell/filament-page-builder)
[![License](https://img.shields.io/packagist/l/carljanzell/filament-page-builder.svg?style=flat-square)](https://github.com/Darkify19/filament-page-builder/blob/main/LICENSE)

**📖 [Documentation](https://darkify19.github.io/filament-page-builder/)**

</div>

> **Status: the canvas is a nested layout editor.** Palette with layout
> primitives (including Embed), drag into columns, a document outline, token style
> inspector, inline plaintext editing, undo/redo, ghost recovery and an optimistic
> save lock — all persisting to the same JSON the form editor uses. On a phone,
> Design mode is a one-panel editor (Blocks / Page / Settings). Still to come:
> rich text in place, an image picker, draft/publish and reusable sections. See
> [ROADMAP.md](https://github.com/Darkify19/filament-page-builder/blob/main/ROADMAP.md) and the
> [improvement pack](https://github.com/Darkify19/filament-page-builder/blob/main/docs/improvements/README.md).

## Why

Filament's `Builder` field is an excellent structured editor, but it is a *form*: a vertical
stack of collapsible panels. You cannot drop a block where you want it on the page, or see
the layout you are actually building. This package adds a canvas alongside it — both editing
surfaces read and write the same JSON, so neither owns the content.

## Design principles

- **The package owns the mechanism, the application owns the content.** Blocks are classes in
  your app, free to query your models and render your markup. The package never ships a `Page`
  model or a migration.
- **One registry, one set of components.** The form, the canvas and the public renderer all
  resolve through the same registry, so a block cannot mean different things in each.
- **Consumers never run a bundler.** The canvas assets are shipped ready to serve and
  registered under the package's own namespace, so they never touch your application's
  build. This was written for hosts with no Node installed.
- **Unknown block types are skipped, not fatal.** Content outlives schema changes.

## Installation

Requires **PHP 8.3+** and **Filament 3.3.53+**.

> This is the **Filament 3** branch. For Filament 4.12.6+ and 5.x, see
> [`main`](https://github.com/Darkify19/filament-page-builder). On a Filament 3 panel,
> `composer require` picks the Filament 3 release on its own. Differences from `main`:
> the Code block edits in a monospaced textarea (Filament 3 has no code editor field),
> and the Style tab's alignment buttons have no tooltips.

```bash
composer require carljanzell/filament-page-builder
```

The service provider is auto-discovered. Publish the canvas assets. If your `composer.json`
already runs `php artisan filament:upgrade` after autoload, this happens on every install:

```bash
php artisan filament:assets
```

Register the plugin on a panel:

```php
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;

$panel->plugin(
    FilamentPageBuilderPlugin::make()
        ->blocks([
            HeroBlock::class,
            RichTextBlock::class,
        ])
        // Section, text, image, button, embed, spacer and divider ship with the package.
        // Register a class with the same type() to replace one.
        ->recordModel(\App\Models\Page::class) // stored, unused by the canvas
        ->blocksAttribute('blocks'),
);
```

Apply the trait to the model that stores blocks:

```php
use CarlJanzell\FilamentPageBuilder\Concerns\HasBlocks;

class Page extends Model
{
    use HasBlocks;
}
```

## The canvas

Extend the packaged page and bind it to your resource:

```php
use CarlJanzell\FilamentPageBuilder\Filament\Pages\DesignPage as BaseDesignPage;

class DesignPage extends BaseDesignPage
{
    protected static string $resource = PageResource::class;
}
```

Register it as a resource page and the canvas is available at
`/admin/pages/{record}/design` as a full-screen editor — Filament's sidebar and
page heading stay behind so the page itself is the workspace:

```php
public static function getPages(): array
{
    return [
        // …
        'design' => DesignPage::route('/{record}/design'),
    ];
}
```

Blocks are mutated in memory and written on an explicit save, so a drag never waits on a
database round trip. Drop a **Section** to get columns; drag text, images and your own
blocks into a column. Click a block and open the **Style** tab for
padding, width, background and alignment — tokens, not raw CSS.

### Making the canvas match your site

The package styles the builder chrome but knows nothing about how you style your blocks.
Point it at a view that supplies your design tokens and block stylesheet:

```php
FilamentPageBuilderPlugin::make()
    ->canvasStylesView('filament.pages.canvas-styles')
```

Scope that view's rules to `.fpb-canvas`. A bare `body` or `h2` rule leaks out of the
canvas and restyles the builder around it.

### Theming the chrome

The builder follows the panel's light and dark mode on its own. Four variables override
what it picks, set on `:root` for light and on `html.dark .fpb` for dark:

| Variable | Default | What it paints |
| --- | --- | --- |
| `--fpb-editor-bg` | `#f4f4f5` | Behind the whole editor |
| `--fpb-panel-bg` | `#fff` | The toolbar, palette and inspector |
| `--fpb-raised-bg` | `#fff` | The selected tab and preview-width pill |
| `--fpb-canvas-bg` / `--fpb-canvas-fg` | `#fff` / `#111827` | The page preview itself |

The canvas keeps its light default in dark mode, because it is a preview of a public
page rather than part of the admin chrome. Change the last pair if your site is dark.

## Editing on the page

A block can open its own text fields for editing directly on the canvas. Declare which
fields, then mark the matching element in your own markup:

```php
use CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable;
use CarlJanzell\FilamentPageBuilder\Editable;

class HeroBlock implements PageBlock, InlineEditable
{
    public static function editables(): array
    {
        return [
            'heading' => Editable::text()->placeholder('Write a heading'),
            'subheading' => Editable::text()->multiline(),
        ];
    }
}
```

```blade
<h1 @editable('heading')>{{ $data['heading'] ?? '' }}</h1>
```

`@editable` expands to editing attributes while the canvas is rendering and to nothing
anywhere else, so the public page ships the same markup without them — one component,
two contexts.

The declaration is the authority, not the markup: `@editable` on a field the block never
listed emits nothing, and the canvas independently refuses to write an undeclared field, a
value of the wrong kind, a `richText` field (until TipTap is mounted in place), or a
block the current user may not author.

## Defining a block

```php
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use Filament\Forms\Components\TextInput;

class HeroBlock implements PageBlock
{
    public static function type(): string { return 'hero'; }
    public static function label(): string { return 'Hero'; }
    public static function icon(): ?string { return 'heroicon-o-photo'; }
    public static function view(): string { return 'blocks.hero'; }
    public static function fileFields(): array { return ['image']; }
    public static function isVisible(): bool { return true; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->required(),
        ];
    }
}
```

`fileFields()` is explicit rather than inferred: an upload is an array in form state but a
plain path once stored, and a map of strings is indistinguishable from a repeater item.

## Rendering publicly

A naive `@foreach` of the stored array will also print the children of a section as
top-level blocks. Use the shipped renderer, which walks the tree:

```blade
<x-page-builder::blocks :blocks="$page->blocks" />
```

Each column renders as a `.fpb-slot` inside its `.fpb-section`. That wrapper is what
keeps a column's blocks in that column — a section is a grid, and without it every
block becomes its own grid cell. Ship the package stylesheet on the public site, or
give `.fpb-section` and `.fpb-slot` the equivalent rules in your own theme.

A public page is not inside a panel, so the renderer uses the blocks your **default**
panel registered. If the plugin is on a different panel, name it, or every block is
unknown and the page renders empty:

```blade
<x-page-builder::blocks :blocks="$page->blocks" panel="admin" />
```

## Tests

```bash
composer install
vendor/bin/pest
```

The suite boots a real Filament panel under Testbench, with its own resource, canvas page
and block fixtures.

## Contributing

Issues and pull requests are welcome on [GitHub](https://github.com/Darkify19/filament-page-builder).
Work lands on `dev`, so open pull requests against `dev`, and run `vendor/bin/pest` and
`composer lint` before you push.

Report security problems privately, as
[SECURITY.md](https://github.com/Darkify19/filament-page-builder/blob/main/SECURITY.md)
describes, rather than in a public issue.

## Licence

The MIT licence. See [LICENSE](https://github.com/Darkify19/filament-page-builder/blob/main/LICENSE).
