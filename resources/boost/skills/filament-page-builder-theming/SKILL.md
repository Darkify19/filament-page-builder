---
name: filament-page-builder-theming
description: Make carljanzell/filament-page-builder match the application's own design — brand colours, fonts, block styles, style tokens, the editor chrome and dark mode. Use when styling page-builder blocks, making the design canvas look like the public site, rebranding the editor, adding or restyling styleTokens, writing a canvasStylesView, or fixing blocks that look different in the editor, the Preview and on the live page.
---

# Theming filament-page-builder

The same blocks render on two surfaces, and they do not share a stylesheet unless you make them:

| Surface | Wrapper | CSS that is loaded |
|---|---|---|
| Editor canvas | `.fpb-canvas`, inside the Filament panel | Filament's panel CSS, the package's `page-builder.css`, and the view passed to `canvasStylesView()`. **The site's own CSS is not loaded.** |
| Preview button | `.fpb-canvas` around `.fpb-page`, a standalone page in an iframe | `page-builder.css` and the `canvasStylesView()` view. No panel CSS, no site CSS. |
| Public page | `.fpb-page`, from `<x-page-builder::blocks>` in the app's layout | The site's CSS, plus the package stylesheet only if the layout has `<x-page-builder::styles />`. |

Theming means getting the same block styles onto all three, then optionally tuning the editor chrome. Never edit anything under `vendor/`: every setting below lives in the application.

## 1. Read the app's theme before writing CSS

Use the real values. Do not invent a palette.

- **Panel:** `app/Providers/Filament/*PanelProvider.php`. Look at `->colors()`, `->font()`, `->viteTheme()` and the options already passed to `FilamentPageBuilderPlugin::make()`.
- **Site:** the CSS entry (`resources/css/app.css`: Tailwind v4 `@theme` tokens, custom properties), `tailwind.config.js` for Tailwind v3, and the public layout (`resources/views/layouts/` or `components/layouts/`) for fonts and wrapper classes.
- **Blocks:** the app's classes that implement `PageBlock` and the Blade views their `view()` returns. Note whether they use Tailwind utilities or their own classes.
- **Public render:** where the app calls `<x-page-builder::blocks :blocks="…" />`.

## 2. One block stylesheet, loaded on every surface

Write block CSS once, with every selector starting with `:is(.fpb-canvas, .fpb-page)`. Then it cannot leak into the admin UI, and it outranks the package defaults whatever the load order:

```css
/* resources/css/page-blocks.css */
:is(.fpb-canvas, .fpb-page) {
    --fpb-accent: #7b1113;            /* the shipped button and check-list marker */
    font-family: 'Inter', sans-serif;
}
:is(.fpb-canvas, .fpb-page) .hero { padding-block: 6rem; }
```

Load it in the public layout the way the app loads its other CSS (`@vite`, or `asset()`), after `<x-page-builder::styles />`. Also load it in the canvas:

```php
FilamentPageBuilderPlugin::make()
    ->canvasStylesView('filament.page-builder.canvas-styles')
```

```blade
{{-- resources/views/filament/page-builder/canvas-styles.blade.php --}}
@vite('resources/css/page-blocks.css') {{-- add it to vite.config.js input --}}
<style>
    .fpb-canvas { --fpb-canvas-bg: #fffaf3; --fpb-canvas-fg: #1f2937; }
</style>
```

- **Scope every rule.** The view is included inside `.fpb-canvas`, in the same document as the admin panel. A bare `body`, `h2` or `a` rule restyles Filament around the canvas. That is also why the site's whole `app.css`, with its resets, must never be loaded here.
- **Keep the view to stylesheet tags** (`@vite`, `<link>`, `<style>`). It is rendered again on every Livewire update.
- **Tailwind utilities in block views:** the canvas has only the utilities Filament's own theme happened to compile, and the Preview has none. Give the canvas a utilities-only build with no preflight, so nothing resets the admin UI. Put it at the top of `page-blocks.css`:

  ```css
  @layer theme, base, components, utilities;
  @import "tailwindcss/theme.css" layer(theme);
  @import "tailwindcss/utilities.css" layer(utilities);
  @source "../views/blocks";      /* wherever the block views live */
  /* then the site's @theme { … } tokens the utilities rely on */
  ```

  On Tailwind v3, use a separate build with `corePlugins: { preflight: false }` and `content` pointing at the block views.

## 3. Style tokens: the Style tab's presets

The Style tab stores token *names*. They render as `data-fpb-{token}="{value}"` on `.fpb-block` in the canvas and on `.fpb-el` on the public page. The package styles these defaults:

| Token | Values |
|---|---|
| `padding` | `none` `sm` `md` `lg` `xl` |
| `background` | `none` `surface` `muted` `contrast` |
| `width` | `narrow` `default` `wide` `full` |
| `align` | `start` `center` `end` |

To restyle a token, cover **both** forms. If you style only one, the editor and the live site disagree:

```css
/* background and align: the canvas body, the public wrapper */
:is(.fpb-canvas, .fpb-page) .fpb-block[data-fpb-background="muted"] > .fpb-block-body,
:is(.fpb-canvas, .fpb-page) .fpb-el[data-fpb-background="muted"] { background: #f5efe6; }

/* padding: the block's own root element in the canvas, the wrapper in public */
:is(.fpb-canvas, .fpb-page) .fpb-block[data-fpb-padding="lg"] > .fpb-block-body > *,
:is(.fpb-canvas, .fpb-page) .fpb-el[data-fpb-padding="lg"] { padding: 3rem; }

/* width: the wrapper itself on both */
:is(.fpb-canvas, .fpb-page) .fpb-block[data-fpb-width="wide"],
:is(.fpb-canvas, .fpb-page) .fpb-el[data-fpb-width="wide"] { max-width: 80rem; margin-inline: auto; }
```

Custom tokens come from `->styleTokens([...])`:

```php
FilamentPageBuilderPlugin::make()->styleTokens([
    'padding' => FilamentPageBuilderPlugin::defaultStyleTokens()['padding'],
    'background' => ['none' => 'None', 'maroon' => 'Maroon', 'sand' => 'Sand'],
])
```

- It **replaces** the defaults. Copy any default set you still want, as above.
- Token keys must match `/^[a-z-]+$/`. Any other key is saved but never rendered.
- Don't name a token `field`, `block`, `kind`, `multiline`, `placeholder`, `parent` or `slot`. Those `data-fpb-*` names belong to the editor.
- Use string keys for values (`'sand' => 'Sand'`).
- Every new value needs CSS in both forms. The package only styles the default names.

To keep editors on the brand presets, add `->customStyles(false)`. That hides the free-form spacing, colour and background pickers.

## 4. The blocks the package ships

Restyle these under the same scope. Setting `--fpb-accent` once recolours the button and the check marker.

- `.fpb-heading[data-fpb-level="h1"]` … `h6`
- `.fpb-text`, `.fpb-quote`, `.fpb-divider`, `.fpb-image`
- `.fpb-button`: background `var(--fpb-accent)`, white text. If the brand accent is light, set `color` too.
- `.fpb-list[data-fpb-marker="check"]`: the marker uses `var(--fpb-accent)`.

`.fpb-section` is the column grid and `.fpb-slot` is one column. Restyle their spacing freely, but leave the section's `display` and `grid-template-columns`, and the slot's `min-width: 0`, alone. The column layout depends on them.

## 5. Breakpoints

The editor's Tablet and Mobile buttons narrow `.fpb-canvas`, a container named `fpb-canvas`, not the window. `@media` queries don't fire there. Write each breakpoint twice, as the package does:

```css
@media (max-width: 640px) { .fpb-page .hero { padding-block: 3rem; } }
@container fpb-canvas (max-width: 640px) { .hero { padding-block: 3rem; } }
```

## 6. The editor chrome (optional)

The editor already follows the panel: light and dark mode, and an accent taken from the panel's primary colour (selection outline, drop markers, active states). Override it only when the editor should look different from the panel.

These variables belong to the panel, not the canvas view. Put them in the panel's theme CSS if it has one. Otherwise use a render hook:

```php
use Filament\View\PanelsRenderHook;

$panel->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => '<style>
    :root { --fpb-editor-accent: #7b1113; --fpb-editor-accent-strong: #5c0d0e; }
    html.dark .fpb { --fpb-panel-bg: #1c1917; }
</style>');
```

| Variable | Default (light / dark) | What it paints |
|---|---|---|
| `--fpb-editor-bg` | `#f4f4f5` / `#18181b` | Behind the whole editor |
| `--fpb-panel-bg` | `#fff` / `#27272a` | Toolbar, palette, inspector |
| `--fpb-raised-bg` | `#fff` / `#3f3f46` | The selected tab and the preview-width pill |
| `--fpb-editor-accent` | the panel's `primary-600` | Selection, drop markers, active states |
| `--fpb-editor-accent-strong` | the panel's `primary-700` | Fills behind white text. Keep it at 4.5:1 or more against white. |
| `--fpb-canvas-bg` / `--fpb-canvas-fg` | `#fff` / `#111827` | The page preview. These describe the site, so set them in the canvas view (section 2). |

- Light values go on `:root`. Dark values go on `html.dark .fpb`, where the package sets its own dark defaults, so a `:root` value wins only in light mode.
- The canvas stays light in dark mode on purpose, because it previews the public page. A dark site sets the canvas pair.

## 7. Check it

1. Run `php artisan filament:assets` after updating the package. The stylesheet is published, not built.
2. Open a page's design view. Try each token value and select a block. The outline should use the accent.
3. Switch the panel to dark mode. The chrome should stay readable, and the canvas should still look like the site.
4. Try Tablet and Mobile, then the Preview button.
5. Open the public page. It should match the canvas. If columns stack or spacers have no height, the layout is missing `<x-page-builder::styles />`.
