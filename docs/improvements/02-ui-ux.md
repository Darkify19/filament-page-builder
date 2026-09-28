# 02 — UI and UX

Chrome, canvas, inspector, outline, keyboard, and accessibility. The
mutation model is sound. The gaps are discoverability, context, honest
preview, and a few correctness bugs that feel like UX failures.

Architectural constraints that this file will not break:

- No second renderer and no iframe canvas
- Tokens, not a CSS textarea
- No consumer bundler
- Native DnD stays until SortableJS is a deliberate, vendored UMD
- `pointer-events: none` on `.fpb-block-body` stays the default
  (an “interactive preview” toggle may opt out)
- Explicit save; no database write per drag

---

## What already works

- Full-screen three-column shell (palette · canvas · inspector) at
  `100dvh`, Filament chrome hidden via `fpb-edit-mode`
- Real Blade blocks on the canvas, with `canvasStylesView()` so the
  preview matches the public site
- Light canvas in dark admin mode (the canvas is a page preview)
- Drag into slots, click-to-insert at the selection, drop marker,
  autoscroll, enter / exit / FLIP motion
- Inline plaintext with inspector sync and morph-skip on the focused
  field
- Outline mirrors the tree; Content / Style inspector; shortcut legend
- Reduced motion respected in JS and CSS

---

## Priority

### P0 — correctness and access

| Issue | Where | Change |
|---|---|---|
| ⌘Z / ⌘S / ⇧⌘Z fire while typing | `page-builder.js` `onKeydown` handles them before `isTyping()` | Flush the editable, then save. Ignore layout undo while the caret is in a field |
| Tabs have `role="tab"` but no `aria-selected` / `aria-controls` | `design.blade.php` palette and inspector | Full tab pattern |
| Block toolbar is unicode (handle, duplicate, delete) with `title` only | `canvas-block.blade.php` | `aria-label` + Heroicons |
| Empty-state copy assumes the user already knows “section” and “column” | `design.blade.php` `@empty` | Primary CTAs + starter layouts — see [03](03-editor-journeys.md) J1 |
| `--fpb-depth` is set on outline nodes and never styled | `structure-node.blade.php` | Indent by depth, or drop the unused variable |

### P1 — daily friction

| Issue | Change |
|---|---|
| No palette search | Sticky filter over `paletteGroups` |
| Icon + label only; no description or thumbnail | Optional `PageBlock::description()`; wireframe cards for layout types |
| Outline is select-only | Collapse, icons, slot labels, scroll-into-view, later drag-to-reorder |
| No breadcrumbs | `Page › Section › Column 2 › Text` in the chrome, each segment selectable |
| Style tab is raw `<select>` of token names | Button groups / swatches bound to the same `wire:model.live` values |
| Preview is a max-width squeeze | Width badge (“390px”); `container-type` on `.fpb-canvas`; honest label |
| Shortcuts use ⌘ only | Platform-aware `Ctrl` / `⌘`; `?` opens a help modal |
| Palette item is both `draggable` and `wire:click` | Drag threshold so a tiny drag does not also insert |
| Below 1280px the grid stacks | **Done** — Bottom nav: Blocks · Page · Settings |
| Empty inspector mentions a Style tab that is not rendered | Drop that sentence, or always show the tabs |

### P2 — polish

Command palette (`⌘K`), empty-state template gallery, canvas zoom,
save timestamp, density mode, column labels on filled sections,
optional interactive-preview toggle, accent colour as `--fpb-accent`
instead of hardcoded `#2563eb`.

---

## Surfaces to add

### 1. Command palette — `⌘K` / `Ctrl+K`

Alpine-only. Fuzzy insert, “go to block”, save, undo, preview width.
Calls the `$wire` methods that already exist.

### 2. Selection breadcrumbs

Needs an ancestor chain from `BlockTree`. Click a segment to select
that ancestor. Inspector title becomes `Paragraph › Body`, not
“Block settings”.

### 3. Empty-state gallery

Three preset trees, inserted only when the page is empty:

- Department page — hero, two-column body, CTA, footer text
- Article — single column, heading + text + image
- Blank section — one section, two columns

The package ships the *mechanism* (insert a tree). The application
can replace the presets. See [03](03-editor-journeys.md).

### 4. Shortcuts modal

Triggered by `?`. OS-aware modifiers. Replaces the easy-to-miss
legend at the bottom of the palette.

### 5. Palette search + descriptions

Non-breaking `description(): ?string` on a block class. Filter is
client-side.

### 6. Outline upgrades

Collapse containers. Show `Row 2 › Column 1`. “Reveal on canvas”.
A **Hidden (n)** group for ghosts (B4 / B2 in [01](01-reliability.md)).
Drag-to-reorder later, through the existing `moveBlock` contract.

### 7. Visual token pickers

Same stored strings. The application may pass a label map
(`'lg' => 'Spacious'`) and, later, a colour for swatches. The
package must not invent CSS.

### 8. Narrow-layout chrome **done**

Bottom nav switching `x-show` panels (Blocks / Page / Settings).
Floating “+” opens the palette from the Page tab. Selected block
keeps Edit and ↑/↓ — native drag is off below 1280px.

### 9. Width readout

Next to the preview toggle: `768px` / `390px`.

### 10. “Cannot edit here” cue

Dashed overlay on a rich-text node that is still inspector-only,
pointing at the Content tab. Goes away when TipTap mounts in place.

---

## Topic notes

### Empty state

Today: one centred paragraph, and the same hint in Structure. Slot
empties already say “Drop a block here”. Missing: a primary button,
a template picker, and a placement rule that updates with
selection (“Appends at the end of the page” vs “Inserts after the
selected block” vs “Lands in this section’s first column”).

### Palette

Click-to-insert with nothing selected appends as a root — correct,
but unstated. With a section selected it always uses the first
slot, not “the column under the pointer”. Say so in the hint, and
add an in-column “+ Add content” menu so the first page does not
require a precise drag.

### Inspector

Unknown and unauthorised blocks already have clear copy. Keep that
tone. Style tokens should never show machine names when the app
supplied labels. Group spacing / colour / layout. Scroll the
selected block into view when a token changes.

### Canvas chrome

Hover outline is a light grey — weak on busy pages. The block bar
is hover-only, so it vanishes on touch. Persist the bar while
selected. Stronger selected ring; a quieter parent ring when a
child is selected.

### Responsive preview

Do not add phone bezels unless they help orientation. Do not iframe.
The honest move is a width badge plus `@container` on `.fpb-canvas`,
documented for app block CSS. A signed live-preview link (Stage D)
is the only way to show the real public layout.

### Keyboard

Shortcut map lives in `design.blade.php` 128–137 and
`page-builder.js` `onKeydown`. P0 is the typing conflict. After
that: `?` help, platform labels, and a keyboard insert path (`⌘K`
or Insert) because today you cannot add a block without the mouse
if you also need to choose a column.

### Accessibility

Preview group has `aria-label`. Undo / redo icon-buttons have
Filament `label`s. Everything else is short: tabs, skip link to
the canvas, live region on insert (“Paragraph added to column 2”),
focus-visible on outline buttons, labelled block tools.

### Narrow editor

`@media (max-width: 1280px)` stacks the grid and lets the page
scroll. A laptop in split-screen cannot see palette, canvas, and
inspector together. Treat that as a three-panel switcher, not a
tall scroll.

### Dark mode and density

The chrome / canvas split is correct. Accent `#2563eb` is hardcoded
in hover, selection, and markers — lift it to `--fpb-accent` so a
panel primary can win. Optional `data-density="compact"` later.

### Motion

Keep the Anime.js vocabulary (`motion.play` / `flash` / FLIP). Add
a CSS pulse fallback when the library is missing so an insert still
answers “where did it go?”. Failed drops (invalid parent) should
toast, not fail silently.

---

## Copy to ship with the chrome changes

| Surface | Current | Proposed |
|---|---|---|
| Empty canvas | “This page has no blocks yet. Drag one in from the left, or drop a section to start a layout.” | “Start from a layout” + three cards + “or drag a block from the left” |
| Palette hint | “Drag onto the page or into a column. Click to insert at the selection.” | Dynamic: “Click to add at the end of the page” / “Click to add after the selected block” / “Click to add in this row’s first column” |
| Section palette label | Section | Row & columns — Side-by-side areas for content |
| Text palette label | Text | Paragraph — Body copy you can edit on the page |
| Style hint | “Tokens from your theme, not raw CSS.” | “Look & spacing. Options come from your organisation’s brand guide — you cannot break the layout.” |
| Save status (clean) | All changes saved | Saved · Last saved 2:14 PM |
| Save status (dirty) | Unsaved changes | Draft not saved · Save now |
| Delete (content) | Browser `confirm` | Inventory dialog: “Delete this row and everything inside it?” + list |
| Form editor (nested page) | Always shown | “Advanced: form view — not recommended for pages built here” + confirm |

Never show token keys (`sm`, `data-fpb-padding`) in the UI when a
label exists. Never mention JSON, slots, or `parent` to an editor.

---

## What not to change

| Constraint | Why |
|---|---|
| One Blade view for public + canvas | Preview must not drift |
| Tokens, not raw CSS | Brand guardrail |
| No consumer bundler | Hosts without Node |
| Inline canvas, not iframe | Drag and Livewire |
| Explicit save | A drag is not a database write |
| Session undo (for now) | Revisions are Stage D, not chrome polish |
| Default `pointer-events: none` on the block body | Click selects; editables and slots opt back in |
| Filament `.fi-*` full-screen hooks | Fragile, but intentional; `check-drift.sh` watches them |
