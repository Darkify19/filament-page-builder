# Agent guide: filament-page-builder

A Filament plugin (not an app) for a drag-and-drop page builder that stores content as typed blocks. v0.4.x targets Filament 4.13.2+ and 5.8.2+. The 0.3.x line for Filament 3 is separate.

- **Using the plugin in an app?** Read [resources/boost/guidelines/core.blade.php](resources/boost/guidelines/core.blade.php).
  Apps with [Laravel Boost](https://github.com/laravel/boost) get it in their own agent guidelines automatically on `php artisan boost:install` / `boost:update`.
- **Working on the plugin itself?** Read on.

## Commands

```bash
composer install
vendor/bin/pest --compact                # full suite, as CI runs it (there is no composer test script)
vendor/bin/pest --filter="moves a block" # a subset
composer lint                            # pint --test, check only; CI fails on style
composer format                          # pint, rewrites files
```

Deep-dives for working on this package (storage, canvas, frontend, blocks, rendering, testing) are in [.claude/skills/filament-page-builder/SKILL.md](.claude/skills/filament-page-builder/SKILL.md).

## Where things are

| Path | What |
|---|---|
| `src/FilamentPageBuilderPlugin.php` | Fluent config, `register()` fills the panel's block registry |
| `src/BlockRegistry.php`, `src/BlockRegistries.php` | One registry per panel; `current()` resolves the one being served |
| `src/PageBuilder.php` | Render-context stack, editable attributes, `slot()`, `text()`, `markup()` |
| `src/PageBuilderServiceProvider.php` | Views namespace, the `editable` directive, asset registration |
| `src/Filament/Pages/DesignPage.php` | The canvas: mutations, save, undo |
| `src/Blocks/` | Shipped block primitives (section, text, image, ...) |
| `src/Support/BlockTree.php` | The flat tree: `parent`, `slot`, `position` |
| `src/Concerns/HasBlocks.php` | Model trait for the blocks column |
| `resources/views/components/` | Public and canvas renderers (`blocks`, `public-block`, `section`, `styles`) |
| `resources/js/page-builder.js`, `resources/css/page-builder.css` | Canvas UI. No build step, ever |
| `resources/lang/` | Translations, seven locales |
| `resources/boost/guidelines/core.blade.php` | Agent guide shipped to apps |
| `resources/boost/skills/filament-page-builder-theming/SKILL.md` | Theming skill shipped to apps |
| `tests/Feature/AgentGuideTest.php` | Fails if a plugin method is missing from the agent guide |
| `tests/Fixtures/` | Test panels, page model, fixture blocks |
| `ROADMAP.md`, `docs/improvements/` | The plan, and the decisions already made |

## Rules

- Read the **Decided** and **settled** sections of [ROADMAP.md](ROADMAP.md) before changing behaviour. They are settled; change them only on purpose.
- **Public API changes update the docs in the same commit.** A plugin method, a block or container contract method, an `Editable` kind, a `PageBuilder` method, a shipped block's `type()`, or a Blade component: update [README.md](README.md) and [resources/boost/guidelines/core.blade.php](resources/boost/guidelines/core.blade.php) together. `AgentGuideTest` catches a missing plugin method, not the rest.
- A shipped block's `type()` is stored in every page's JSON. Never rename it.
- No build step. The canvas JS is hand-written and shipped unminified; do not add npm, a bundler or a CDN dependency.
- The package never ships a model, a migration or an app's block. Keep app-specific names out of `src/`, `resources/` and the docs.
- Consumers see JS and CSS changes only after `php artisan filament:assets`. Say so in the PR when a change touches them.
- Any change must work on Filament 4 and 5 (the CI matrix in `.github/workflows/ci.yaml`).

## Branches

- `dev` is where work lands. Open pull requests against `dev`.
- `.github/workflows/auto-merge.yaml` promotes `dev` to `main` when the build is green.
- `main` is the 0.4.x line for Filament 4 and 5.
- `filament-3` is the separate 0.3.x line for Filament 3, with its own `v0.3.x` tags.
