# Filament Page Builder — Roadmap to full page control

**Status:** Stage 0 (Livewire tests, data-loss fixes, undo, guards) and
Stage C **core** (flat tree, containers, token inspector, preview width
toggle, plaintext inline edit) are shipped. The reliability queue from
[docs/improvements/01-reliability.md](docs/improvements/01-reliability.md)
is in: reminted clone ids, optimistic lock, SafeUrl / MediaUrl, section
ratio sync, ghost recovery, saved-state dirty flag, `renderSafely`, and
the Now items Embed / anchor / palette search. Stage B is still
**partial** (plaintext only — rich text, image picker and link popover
are not in place). Not shipped: browser tests, draft / publish,
per-breakpoint visibility, a `{"version":2}` storage wrapper, or
`pages:upgrade-blocks`. The suite is Pest + Livewire only.

Next up: rich text in place (Filament TipTap), then Stage D
(draft / publish). The planning brief is
[docs/improvements/](docs/improvements/README.md).

**Where we want to get to:** the editor manipulates the *page*, not a list of panels —
click a heading and type into it, drop a text box into a column, set spacing and
background from a constrained token set, and never touch the form editor unless they
want to.

---

## 1. The architectural fork

Everything below hangs off one decision, so it goes first.

### Option 1 — Element-level editing, block-level layout *(recommended)*

Typed blocks stay the unit of layout. What changes is that every text node, image and
link *inside* a block becomes directly editable on the canvas, and every block gains a
constrained style panel. We add a container block family (Section → Columns → children)
so editors can compose freely where they need to.

- Keeps the package's central premise: **blocks are your app's Blade components, free to
  query your models and render your branding.**
- One component serves the public site and the canvas; no second renderer to drift.
- The editor can't produce off-brand output, because the knobs are tokens, not CSS.

### Option 2 — Free-form canvas (Elementor / GrapesJS class)

Absolute or flow positioning of arbitrary primitives. The page becomes a generic element
tree; PHP blocks become one element type among many.

- Maximum power, and what a non-technical editor recognises from Wix.
- Costs the premise. A generic tree can't call `Personnel::query()`. Block styling moves
  from your stylesheet into per-element JSON, so the site drifts off-brand within weeks
  and responsive behaviour becomes the editor's problem instead of yours.
- Adopting **GrapesJS** gets this for free but it owns its own data format and rendering
  pipeline. It would be a different product wearing this package's name.

### Option 3 — Hybrid

Option 1, plus one `FreeCanvasBlock` that *is* a mini GrapesJS-style surface for the rare
poster-like section. Escape hatch without giving up the model.

> **Decided: Option 1, with Option 3 kept on the table for Stage E.**
> The value of this package is that a Hero block renders the *same* Blade component the
> public site renders. Everything below assumes that stays true.

### Two further decisions, settled

- **"Textboxes" means draggable text boxes** — a text primitive you drop
  anywhere and type into. *Not* on-page form inputs. Form submissions are out of scope;
  Stage E is the free-canvas hatch only.
- **The no-build rule is broken**, deliberately and only inside this repo. The package may
  ship committed prebuilt JS. Consumers still never run a bundler — see §6.

---

## 2. Stage 0 — foundation and debt ✅ done

The documented limitations are load-bearing once inline editing multiplies the number of
state mutations. Fix them before building on top.

| # | Item | Why now |
|---|------|---------|
| 0.1 🟨 | **Test suite.** Pest 4 + `livewire()` tests for every `DesignPage` mutation. **No** browser tests for drag, drop or typing yet — `scripts/check-drift.sh` is the JS contract check. | Stage B/C are refactors of exactly this code. Without tests they are guesswork. |
| 0.2 ✅ | **Stop destroying unknown block types on save.** Keep unrecognised entries in place (render nothing, show a "retired block" placeholder on the canvas) instead of pruning at load and writing the pruned array back. | This is a silent data-loss bug today. |
| 0.3 🟨 | **`blocksAttribute()` is honoured.** `recordModel()` is stored and unused — kept as a fluent so existing calls stay valid. | The canvas binds the resource record, not a configured model class. |
| 0.4 ✅ | **Preserve unknown keys on a block.** Merge rather than rebuild as exactly `id/type/data`. | Stage C adds `settings`, `parent_id`, `position` — the rebuild would eat them. |
| 0.5 ✅ | **Per-panel registry.** Registry is a container singleton; two panels merge their block sets. Key it by panel id. | |
| 0.6 ✅ | **Undo / redo.** A capped history stack (`BlockHistory::LIMIT = 30`) in the session, tagged by Livewire component id. Dirty clears when the tree matches the last saved snapshot. | Inline editing makes accidental destruction far easier. |
| 0.7 ✅ | **Unsaved-changes guard.** `beforeunload` + intercept Filament's `wire:navigate`. | |
| 0.8 ✅ | **Delete confirmation** on blocks with content. | |
| 0.9 ✅ | **Keyboard:** ⌘Z/⇧⌘Z, ⌘D duplicate, ⌘S save, Del, ↑/↓ move selection, Esc deselect. | |

---

## 3. Stage B — inline editing ("full control, from texts") — text done, rich text next

The core mechanism. A block declares which of its fields map to which element in its own
markup; the canvas makes those elements editable in place and writes straight back to
Livewire state.

### 3.1 Editable bindings

Blocks opt in through a new optional method (defaulting to `[]`, so every existing block
keeps working):

```php
public static function editables(): array
{
    return [
        'heading'    => Editable::text(),
        'subheading' => Editable::text()->multiline(),
        'body'       => Editable::richText(),
        'image'      => Editable::image(),
        'cta_label'  => Editable::text(),
        'cta_link'   => Editable::link(),
    ];
}
```

The app marks the element in its own Blade component with a directive the package ships:

```blade
<h1 @editable('heading')>{{ $heading }}</h1>
```

`@editable` expands to `data-fpb-field="heading"` **only when rendering inside the
canvas** (`PageBuilder::isEditing()`). The public page ships identical, clean markup. One
component, two contexts — this is the whole trick.

### 3.2 Per-type behaviour

| Type | Canvas behaviour |
|------|------------------|
| `text` | `contenteditable="plaintext-only"`, `Enter` commits, `Esc` reverts. Debounced `$wire` write on blur. |
| `richText` | Mount **Filament's own TipTap bundle** in place. Verified: `filament/forms` registers `AlpineComponent::make('rich-editor', dist/components/rich-editor.js)`, loadable via `ax-load` — so we get TipTap with **no build step and no second HTML producer**. A floating bubble toolbar replaces the inspector panel. |
| `image` | Click opens a Filament modal `Action` on the `DesignPage` — upload or pick from an existing media library. Reuses `BlockStateNormaliser` for the temp-URL preview that already works. |
| `link` | Small inline popover: label + URL, with a route/page picker fed by the consuming app. |
| `list` / repeater | `Enter` splits an item, `Backspace` on empty merges, drag to reorder. |

### 3.3 Selection model

Selection becomes two-level: **block** (outline, toolbar, style panel) and **element**
(caret, inline toolbar). Clicking an editable selects the block *and* scrolls/focuses the
matching field in the inspector, so the two surfaces stay in sync rather than competing.

### 3.4 Consistency

Inline writes and inspector writes hit the same `setBlockField($blockId, $path, $value)`
Livewire method, which funnels through the existing normaliser. There is exactly one path
into block state.

---

## 4. Stage C — structure and style ("textboxes, columns, spacing") ✅ done

### 4.1 Storage — flat tree ✅ (no version envelope)

Nesting is the real cost of containers. The column is still a **bare JSON list**. Each
block carries `id`, `type`, `data`, `parent`, `slot`, `position`, `settings`, plus
passthrough keys (`anchor`, application fields). There is no `{"version":2,"blocks":[…]}`
wrapper and no `pages:upgrade-blocks` command. v1 lists (no tree keys) become roots
inside `BlockTree::hydrate()`. Colliding ids — Filament Builder clones — are reminted
on hydrate so the next save does not drop the copy.

### 4.2 Container blocks (shipped by the package, app-overridable) ✅

- `SectionBlock` — full-bleed or contained, N columns with a ratio picker (1, 1-1, 1-2, 1-1-1, …), per-slot children.
- `SpacerBlock`, `DividerBlock`.
- `TextBlock` — a plaintext, multiline text box (`Editable::text()`, not TipTap).
- `ButtonBlock` (SafeUrl on `href`), `ImageBlock` (public-disk URL at render),
  `EmbedBlock` (allowlisted YouTube / Vimeo / Maps iframe).

Nested drag-and-drop: drop targets become slots, with the marker resolving to the nearest
valid slot. This is where the native HTML5 DnD API starts to hurt — see §6.

### 4.3 The style inspector — tokens, not CSS ✅

A second inspector tab, driven by a schema the *app* supplies so it maps onto its own
design tokens:

```php
FilamentPageBuilderPlugin::make()->styleTokens([
    'padding'    => ['none', 'sm', 'md', 'lg', 'xl'],
    'background' => ['none', 'surface', 'maroon', 'forest', 'image'],
    'width'      => ['narrow', 'default', 'wide', 'full'],
    'align'      => ['start', 'center', 'end'],
])
```

Stored in `settings`, emitted as `data-fpb-padding="lg"` on the block wrapper, styled by
the app's stylesheet. An editor cannot produce a 13px lime heading, which is the point.
`CustomHtmlBlock` remains the escape hatch for the one case a year that needs it.

Per-block **anchor id** is a passthrough key on the block, emitted as `id` on
`.fpb-el` / the canvas wrapper when it is a valid HTML fragment identifier.
Per-breakpoint visibility is **not** shipped.

### 4.4 Responsive preview ✅

Width toggle (desktop / tablet / mobile) on the toolbar. Because the canvas renders inline
rather than in an iframe, this is a `max-width` on the canvas wrapper. `.fpb-canvas` now
sets `container-type: inline-size` so application CSS can use `@container`. The toggle is
a preview width, not a device emulator.

---

## 5. Stage D — workflow and reuse

| Feature | Notes |
|---------|-------|
| **Draft vs published** | Second JSON column; canvas edits the draft, an explicit Publish promotes it. Decouples editing from what visitors see — the single biggest safety win for a live departmental site. |
| **Revisions** | `page_revisions` table, snapshot on publish, diff and restore. Optional trait + migration shipped by the package. |
| **Reusable / global sections** | Save a subtree as a named section; reuse across pages; edit once, updates everywhere. Footers and CTAs are the obvious cases. Stored by reference with a "detach" action. |
| **Page templates** | Start a new page from a saved block layout. |
| **Copy / paste between pages** | Serialise the selected subtree to `localStorage`; paste anywhere. |
| **Multi-select** | Shift-click, then move/duplicate/delete as a group. |
| **Content guardrails** | Server-side lint on save: exactly one `h1`, no skipped heading levels, alt text present, token contrast pairs valid. Surfaces as warnings in the toolbar, not hard failures. |
| **Live preview link** | Signed URL rendering the draft on the real public layout. |

---

## 6. Tech decisions to settle

**Keep the no-build constraint?** It is the package's stated identity and the reason it
works on a Node-less host. I would keep it for *consumers* — but allow a committed,
prebuilt `dist/` inside the package. That is not a consumer build step; it's a file in the
repo. Two things want it:

- **Drag-and-drop.** Native HTML5 DnD is fine for a flat list and genuinely bad for nested
  containers and touch devices. Vendoring a prebuilt SortableJS UMD (~40 KB) into the
  package's own `resources/js/` solves nesting, touch, auto-scroll and drop animation.
  Filament bundles SortableJS but does not expose it as a public global, so we cannot
  borrow it.
- **Rich text.** Already solved without any vendoring — Filament's TipTap bundle is
  reachable through `FilamentAsset::getAlpineComponentSrc('rich-editor', 'filament/forms')`.

**Rejected:** GrapesJS (owns its data model and renderer — see §1), a Vue/React canvas
(second rendering path, and loses the "your Blade component *is* the block" property),
iframe isolation (kills cross-boundary drag and Livewire sync; the current inline trade is
correct).

**Worth adding:** Alpine's `$persist` for editor preferences, `Intl.Segmenter`-free plain
`contenteditable` (no library), and Livewire v3 `wire:dirty` for the status pill.

---

## 7. Suggested sequencing

1. ~~**Stage 0** — Livewire tests, data-loss fixes, undo, guards.~~ Done (browser tests still open).
2. ~~**Reliability queue** — remint, lock, SafeUrl/MediaUrl, ghosts, dirty snapshot.~~ Done.
3. **Stage B** — ~~inline text~~ done; rich text, image picker and link popover still to come.
4. ~~**Stage C core** — flat tree, containers, tokens, preview width, Embed, anchor.~~ Done.
   Breakpoint visibility and a versioned storage wrapper are still open.
5. **Stage D** — draft/publish and revisions before reusable sections.
6. **Stage E** — the `FreeCanvasBlock` hatch, if it is still wanted by then.

---

## 8. Repo and release

- Work lands on **`dev`**. Tag releases when a consumer needs to pin or roll back;
  a `dev-main` version string does not bust published asset caches.
- `docs/index.html` is hand-written and already documents each limitation; keep it in the
  same commit as the fix that removes one.
