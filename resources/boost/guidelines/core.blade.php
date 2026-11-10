## Filament Page Builder (carljanzell/filament-page-builder)

A drag-and-drop page builder for Filament 4.13.2+ and 5.8.2+ panels (this guide covers v0.4.x). Content is an ordered, nested list of **typed blocks** stored in one JSON column on the app's own model. The app owns the block classes and their Blade views; the package owns the canvas, the registry and the public renderer. Editors work on a full-screen **Design** page; the public site renders the same blocks with one Blade component.

### Install

@verbatim
<code-snippet name="Install" lang="bash">
composer require carljanzell/filament-page-builder:^0.4.2
php artisan filament:assets
</code-snippet>
@endverbatim

- `php artisan filament:assets` is required after install and every update: the canvas JS and CSS are shipped as plain assets, not part of the app's build. `filament:optimize` does not publish them.
- The service provider is auto-discovered. No config file and no migration: the package ships no table. The blocks column belongs to your model.
- Filament 3.3.53+ resolves to the separate v0.3.x line. Do not apply this guide's API to it.

### Register on a panel

@verbatim
<code-snippet name="Plugin on a panel" lang="php">
use App\Filament\Blocks\HeroBlock;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;

$panel->plugin(
    FilamentPageBuilderPlugin::make()
        ->blocks([HeroBlock::class])
        ->canvasStylesView('filament.page-builder.canvas-styles')
        ->allowCode(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Model with blocks" lang="php">
use CarlJanzell\FilamentPageBuilder\Concerns\HasBlocks;

class Page extends Model
{
    use HasBlocks;

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }
}
</code-snippet>
@endverbatim

The model needs the `array` cast on the blocks column. To use another column name, set `protected string $blocksAttribute = '…';` on the model, or `->blocksAttribute('…')` on the plugin.

Add the canvas as a page of the resource the model is edited through. The canvas is a full-screen editor at `/admin/pages/{record}/design`, and it needs that resource's `canEdit()` for the record:

@verbatim
<code-snippet name="Design page bound to a resource" lang="php">
use App\Filament\Resources\Pages\DesignPage as BaseDesignPage;

class DesignPage extends BaseDesignPage
{
    protected static string $resource = PageResource::class;
}

// In PageResource::getPages()
'design' => DesignPage::route('/{record}/design'),
</code-snippet>
@endverbatim

### Plugin methods

Every public fluent method on `FilamentPageBuilderPlugin`. Each returns the plugin, so calls chain.

| Method | Default | Notes |
|---|---|---|
| `blocks(array $blocks)` | `[]` (plus the shipped layout blocks) | Block classes implementing `PageBlock`. A class with the same `type()` as a shipped block replaces it. |
| `recordModel(string $model)` | `null` | Stored and read by nothing. Kept so existing calls stay valid. |
| `blocksAttribute(string $attribute)` | `'blocks'` | The JSON column on the record. A model's own `$blocksAttribute` property wins over this. |
| `canvasStylesView(?string $view)` | `null` | Blade view rendered inside the canvas, before the blocks. Injects your tokens and block CSS. |
| `includeLayoutBlocks(bool $include = true)` | `true` | Ships section, heading, text, list, quote, image, button, video, embed, spacer, divider, shortcode and code blocks, plus the `[year]` and `[date]` shortcodes. `false` keeps only your own types. |
| `allowCode(bool\|Closure $condition = true)` | `false` | Who may add and edit the `code` block (raw HTML, CSS and JS that runs on the public page). Closure is evaluated per request. Existing code blocks keep rendering either way. |
| `customStyles(bool $enabled = true)` | `true` | Free-form spacing, colour, background and size in the inspector, beside the token presets. `false` keeps editors to tokens. |
| `shortcode(string $name, callable $callback, string\|Closure\|null $description = null, ?string $example = null)` | none | Registers a `[name]` tag. Nothing is registered by default. See Common tasks. |
| `embedHosts(array $hosts)` | `[]` | Hosts the Embed block may frame, on top of the shipped allowlist. Subdomains are included. Merges across calls. |
| `styleTokens(array $tokens)` | `[]`, which means the built-in presets | Token sets for the Style tab. A non-empty array replaces all of the presets, it does not merge. |

### Common tasks

- **Add a block type:** implement `CarlJanzell\FilamentPageBuilder\Contracts\PageBlock` on a class with static methods, then pass it to `blocks()`. The methods are `type()`, `label()`, `icon()`, `schema()` (Filament form components), `view()`, `fileFields()`, and `isVisible()`. Blade views receive `$data`.
- **Block view:** a `view()` value like `blocks.hero` is a Blade component (`components/blocks/hero.blade.php`) that takes a `data` prop. A value containing `::` is rendered as a view with `$data`.
- **Optional static methods on a block:** `defaults()` (starting data for a dropped block), `category()` (palette group, otherwise `blocks`), `description()` (palette subtitle), `resizable()` (maps a canvas resize axis to a data field, e.g. Spacer returns `['height' => 'size']`).
- **Inline editing on the canvas:** implement `CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable` and return `Editable::text()` entries from `editables()`. Mark the matching element in the block view with the `@@editable('field')` directive. Only fields named in `editables()` are writable. Only `Editable::text()` writes in place for now; `Editable::richText()` is refused by the canvas until the rich text editor is wired in.
- **Containers (columns, groups):** implement `CarlJanzell\FilamentPageBuilder\Contracts\Container` with `slots(array $data)` and `defaults()`. In the view, loop over `PageBuilder::slotNames()` and print `PageBuilder::slot($name)`. Children are stored flat with `parent`, `slot` and `position`, so never nest them in PHP.
- **Render on the public site:** use the shipped component, which walks the tree. Ship the package stylesheet with the other one:

@verbatim
<code-snippet name="Public render" lang="blade">
<x-page-builder::styles />

<x-page-builder::blocks :blocks="$page->blocks" />

{{-- Plugin registered on another panel than the default one: --}}
<x-page-builder::blocks :blocks="$page->blocks" panel="admin" />
</code-snippet>
@endverbatim

- **Shortcodes:** the editor types `[name attr="x"]…[/name]` in text, headings, code, or a Shortcode block, and the callback returns the markup at render time. Callback output is trusted, so escape whatever you echo from `$attributes`.

@verbatim
<code-snippet name="Register a shortcode" lang="php">
FilamentPageBuilderPlugin::make()
    ->shortcode(
        'news',
        fn (array $attributes, ?string $content): string => view('news.latest', [
            'count' => (int) ($attributes['count'] ?? 3),
        ])->render(),
        fn (): string => __('The latest news.'),
        '[news count="3"]',
    );
</code-snippet>
@endverbatim

- **Trusted HTML with shortcodes:** in your own block views, `CarlJanzell\FilamentPageBuilder\PageBuilder::text($value)` escapes a plain field and expands shortcodes on the public page. `PageBuilder::markup($html)` expands shortcodes in HTML you already trust.
- **Translations:** the editor ships in English, Azerbaijani, French, German, Russian, Spanish and Turkish. To change a sentence, run `php artisan vendor:publish --tag=page-builder-translations` and edit `lang/vendor/page-builder`. Your block labels are your own strings. Pass translated descriptions as closures, because shortcodes are registered before the request locale is set.
- **Theming and the editor look:** the Boost skill `filament-page-builder-theming` covers brand colours, fonts, block styles, style tokens, the canvas view and dark mode. Load it before writing CSS for blocks.
- **Events:** the package dispatches none. The canvas saves by assigning the blocks attribute and calling `save()` on the record, so model `saving`/`saved` observers see every save.

### Data

The blocks column holds a flat list. Each entry has `id`, `type`, `data` (the block's form state), `settings` (style-token names), `style` (custom styles), `anchor`, `parent` (the parent block's `id`, or null at the top), `slot` and `position`. The package hydrates it with `CarlJanzell\FilamentPageBuilder\Support\BlockTree`. `HasBlocks::getBlocks()` returns that hydrated list.

Unknown block types are skipped on render and kept in the data, so content survives a removed class. Do not prune them.

### Don't

- Don't `@@foreach` over `$page->blocks` on the public site. Children of a section print as top-level blocks and columns collapse. Use the shipped `blocks` component.
- Don't change a block's `type()` once content exists. The type is the key stored in the JSON.
- Don't make `isVisible()` return `true` for everyone because the palette looks right. The canvas checks it on every write, and it is the only authorisation for blocks.
- Don't turn on `allowCode()` for all editors. Code blocks run with the visitor's full trust on your origin.
- Don't pass one token group to `styleTokens()` and expect the rest to stay. The array replaces the presets, so pass `padding`, `background`, `width` and `align` together.
- Don't expect `recordModel()` to bind anything. The canvas edits the record the resource gives it.
- Don't mark up an element with the `@@editable` directive for a field missing from `editables()`. It emits nothing, and the canvas refuses the write.
- Don't skip `filament:assets` after an update. Without it the canvas loads the previous JS.
- Don't write unscoped CSS in `canvasStylesView`. Rules must start from `.fpb-canvas`, or they restyle the admin panel around it.
