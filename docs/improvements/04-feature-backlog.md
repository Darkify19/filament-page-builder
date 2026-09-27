# 04 — Feature backlog

Now / next / later. Effort is S / M / L relative to this package
(a small, no-bundler Filament library — not calendar time). Each
row notes the reliability item it depends on, if any.

The application still owns branded blocks, the `Page` model,
permissions beyond `isVisible()`, media libraries, SEO, and real
page templates. The package owns the mechanism those sit on.

---

## Now

Finish a credible Stage B and an honesty pass. Do not start draft /
publish until the matching items in [01](01-reliability.md) are
done.

| # | Feature | Effort | Depends on | Notes |
|---|---|---|---|---|
| N1 | ROADMAP and docs audit | S | — | Unmark false ✅. Copy the status line from [00](00-honest-status.md) |
| N2 | Image URL, text `pre-line`, section ratio sync | S | — | A1, A2, A3 |
| N3 | Button URL allow-list | S | — | A4 |
| N4 | Duplicate walks `isVisible()`; remint colliding ids | S | — | C1, B1 |
| N5 | Flush editable before save; ignore layout undo while typing | S | — | D1, D2 |
| N6 | Empty-state copy, palette hint, tab ARIA, labelled block tools | S | — | [02](02-ui-ux.md) P0 |
| N7 | `recordModel()` — wire it or remove it from the public API | S | — | Dead fluent method |
| N8 | Rich text in place (Filament TipTap via `ax-load`) + sanitise `setBlockField` | L | N5, C3, D4 | Bubble toolbar. No second HTML producer |
| N9 | `Editable::image()` + modal `Action` on `DesignPage` | M | N2 | Reuse `BlockStateNormaliser` |
| N10 | `EmbedBlock` (allowlisted iframe hosts) | S–M | — | Promised in ROADMAP §4.2 |
| N11 | Anchor id on every block, emitted as `id` on `.fpb-el` | S | B5 | Avoid token name `field` |
| N12 | Palette search + `description()` | S | — | Client-side filter |
| N13 | One Playwright / Pest browser smoke: drop, type, save | M | — | D4. CI. Keep `check-drift.sh` |

---

## Next

Departmental CMS safety and editing depth.

| # | Feature | Effort | Depends on | Notes |
|---|---|---|---|---|
| X1 | Optimistic lock on save | S–M | — | B7. `updated_at` or `blocks_version` |
| X2 | Draft vs published (trait, second column, Publish action) | M | X1 | Package ships the pattern; the app migrates |
| X3 | Signed live-preview URL helper | M | X2 | App owns the route and auth |
| X4 | `Editable::link()` popover; app supplies a page picker | M | N3 | First consumer is `ButtonBlock` |
| X5 | Ghost recovery panel (orphans + hidden-slot children) | M | — | B2, B4. Also the outline “Hidden (n)” group |
| X6 | Form-editor gate for nested pages; remint on clone | M | N4 | Journey 6 |
| X7 | Visual token pickers + breadcrumbs | M | — | Same stored strings |
| X8 | Starter layout gallery (insert-a-tree mechanism) | M | N2 | App replaces presets |
| X9 | Structure: collapse, icons, reveal, later outline DnD | M | — | |
| X10 | Narrow-layout panel switcher | M | — | Below 1280px |
| X11 | `container-type` on `.fpb-canvas` + docs | S | — | Honest preview |
| X12 | Per-breakpoint visibility in the style inspector | M | X11 | ROADMAP §4.3, never shipped |
| X13 | Copy / paste subtree (`localStorage`) | S–M | N4, B5 | Remint ids on paste |
| X14 | `make:filament-block` artisan command | S | N7 if wired | Class + view + registry snippet |
| X15 | SortableJS UMD, vendored, if nested / touch DnD hurts | M | N13 | Allowed by ROADMAP §6; not required to start |
| X16 | Saved-snapshot dirty flag; document session driver | S | — | E1, E2 |
| X17 | Command palette `⌘K` | M | N12 | Insert, go to, save |

---

## Later

Reuse, quality, and things that wait on a draft column.

| # | Feature | Effort | Depends on | Notes |
|---|---|---|---|---|
| L1 | Revisions (snapshot on publish, restore) | L | X2 | Optional trait + migration |
| L2 | Reusable / global sections (reference + detach) | L | L1, B5 | Edit once, update everywhere |
| L3 | Page templates as import / export of a tree | M | X8 | Library UI is the app’s |
| L4 | Multi-select | M | X15 helps | Shift-click, then move / duplicate / delete |
| L5 | Content lint on save (one `h1`, heading levels, alt, contrast) | M | — | Warnings, not hard failures. App can add rules |
| L6 | List / repeater inline editing | L | N8 | Enter splits, Backspace merges |
| L7 | Typography-scale and hover tokens | M | — | App CSS; package emits `data-fpb-*` |
| L8 | Typed optional statics (`defaults`, `category`, `editables`) | M | — | Typos are silent today |
| L9 | Full browser suite (keyboard + DnD matrix) | L | N13 | |
| L10 | i18n for chrome strings | M | — | `__('page-builder.*')` |
| L11 | Interactive-preview toggle (re-enable pointer events) | M | — | Opt-in; default stays click-to-select |
| L12 | Heading primitive as a reference block | S | — | Or leave it to the app |

---

## Reject

| Feature | Why |
|---|---|
| Option 2 — free-form canvas as the core | Breaks “a block is your Blade component” |
| GrapesJS as the engine | Own data model and renderer = a different product |
| Vue / React canvas | Second rendering path |
| Package-owned `Page` model and site migrations | Stated principle |
| Form-submission blocks | Out of scope (ROADMAP §1) |
| Raw CSS inspector | Violates tokens-not-CSS |
| Built-in media library | The app wires Spatie / disk / whatever it uses |
| Approval / workflow engine | Application concern |
| SEO, search, analytics | Application concern |
| Stage E `FreeCanvasBlock` by default | Defer. If a poster page appears, prefer an app-level HTML block with a strict allowlist |

---

## Package versus application

**Package**

Canvas, tree ops, inline-edit framework, token inspector, draft /
publish *patterns*, revision snapshot *format*, clipboard
serialisation, lint *hooks*, public renderer, retired-block
handling, Embed, anchor id.

**Application**

Hero, faculty list, news, course table, alert, footer, navigation.
Page model, slugs, hierarchy, campus SSO policies. Media library.
Real templates (“Department landing”). SEO. Signed preview route.
Site tokens in CSS and `canvasStylesView`. `@container` rules.
`CustomHtml` for a trusted role. Legacy CMS migration.

---

## Suggested `ROADMAP.md` sequencing (replaces §7)

1. Honesty pass and the twelve reliability items.
2. Stage B — rich text, then image, then link.
3. EmbedBlock + anchor id (the Stage C leftovers).
4. JS smoke + optional SortableJS.
5. Stage D — draft / publish, then preview link and concurrency.
6. Ghost recovery and form-editor hardening.
7. Revisions, then reusable sections and templates.
8. Stage E only if a poster-page need is still real.

Principles that do not change:

- Never add a second renderer.
- Finish inline editing before workflow extras — editors expect
  “click and type” before they care about reusable footers.
- Draft / publish before reusable sections. A global footer on a
  live column without a draft is dangerous.
- Vendor SortableJS when nested drag actually hurts, not
  preemptively. A committed UMD is not a consumer build step.
- The application ships domain blocks. The package ships layout,
  editing mechanism, and Embed.
