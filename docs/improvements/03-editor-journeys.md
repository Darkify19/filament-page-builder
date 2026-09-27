# 03 — Editor journeys

How a non-technical departmental editor would build and keep a real
page (hero, two-column body, CTA, footer-like section) with the
canvas as it is today — and what should change.

The persona thinks in **page sections**, not blocks or slots. They
expect to click a heading and type. They work on Windows as often as
a Mac. They open the editor occasionally, so they will not remember
ratio-versus-columns or the form-editor traps.

---

## Journey 1 — Empty page to a two-column layout

**Today (about 12 actions).** Read the empty-state sentence. Find
Section in the palette. Drag it onto the canvas (or click it). Drag
Text into the left dashed well. Drag Image into the right well — a
near-miss drops it as a sibling of the section. Open the inspector to
upload the image. Click the paragraph to type. Save.

**Breaks.** “Section” is jargon before any columns exist. Click-to-insert
with a section selected always uses `col-0`, not “the column I am
looking at”. The image file lives only in the inspector. After save the
image `src` is a relative path ([01](01-reliability.md) A1). There is
no hero primitive — a hero is Section + Text + Image by hand.

**Change.**

1. Empty state becomes a layout gallery (package mechanism, app
   presets). “Department page” inserts hero / two-column body / CTA /
   footer text in one click.
2. Palette labels: **Row & columns**, **Paragraph**, **Image**.
3. Empty column: inline “+ Add content” (Paragraph, Image, Button).
4. Click the image placeholder → upload modal on the canvas (Stage B).

---

## Journey 2 — Click, type, save

**Today.** Shipped Text is a `<p>`, not a heading. Click → caret,
block selects, inspector Textarea syncs. Blur commits. ⌘S / Ctrl+S
while the caret is still in the field **saves without that edit**
and can read “All changes saved”. Rich text is inspector-only.
Links and buttons on the canvas are inert (`pointer-events: none`).

**Breaks.** No heading block. Two save layers (blur to memory, Save
layout to the database) are unexplained. The shortcut lies.

**Change.** `flushActiveEditable()` before every save / undo /
navigate. Status: **Saved · Last saved 2:14 PM** / **Draft not
saved**. Ship a Heading primitive (or document that the app must).
When TipTap mounts, a floating `[B] [I] [Link]` strip on the caret
— not a second inspector.

---

## Journey 3 — Two columns to three

**Today.** Select the section → Content tab → Columns = Three.
Ratio stays `1-1`. The third column wraps. Dropping 3 → 2 hides
`col-2` children on every surface. The outline does not list them.

**Breaks.** Two independent controls that must agree. Content
disappears with no explanation.

**Change.** One **Column layout** picker that writes `columns` and
`ratio` together (visual 1 / 2 / 3 / 2-wide-narrow tiles). On
shrink, a modal:

> Hide content in the third column?
> · Move to column 2 (recommended)
> · Move to column 1
> · Delete content

Section bar badge: **1 hidden block** → Review.

---

## Journey 4 — Style a block

**Today.** Style tab. Hint about tokens. `<select>` of raw names
(`sm`, `lg`, `maroon`) unless the app passed a label map. Trial and
error against the canvas.

**Change.** Human labels and swatches, same stored values.

| Token | Control | Language |
|---|---|---|
| Padding | Four tiles | Compact · Standard · Roomy · Extra room |
| Background | Swatches | White · Light grey · Brand maroon |
| Width | Icons | How wide this section sits on the page |
| Align | Icons | Align content within this section |

First visit coach mark: these options come from the organisation’s
brand guide; there are no custom colours or font sizes. Never show
`data-fpb-*` or token keys when a label exists.

Optional: a short floating strip on the selected block for padding
and background, with “More in the sidebar”.

---

## Journey 5 — Delete a section that looks empty

**Today.** Hidden-slot children still trip `blockHasContent()`.
Confirm: “Delete this block? Its content goes with it.” The user
thought it was empty. Confirm wipes the subtree. Undo is the only
recovery, and only in this tab, for 30 steps.

**Change.** Inventory dialog:

> Delete this row and everything inside it?
> · 1 paragraph (hidden — was in a removed column)
> · 1 image

“Review hidden content” lists restore / delete per block. Stage D
adds a page trash if revisions exist.

---

## Journey 6 — Form editor, clone, come back

**Today.** Form editor is always offered. Nested children appear as
top-level Builder items. Clone copies the `id` → the clone never
renders → the next canvas save deletes it. Delete a section in the
form → orphan children. Reorder is ignored. Return remounts and
**clears undo**. Last writer wins.

**This journey is unsafe.** Treat the form editor as advanced, not
as an equal surface, until [01](01-reliability.md) B1 / B2 / B3 / B7
are fixed.

**Change.** When any block has `parent !== null`, the button becomes
“Advanced: form view — not recommended for pages built in the visual
editor”, with a confirm. Disable Builder clone or remint ids.
On return: “This page was also edited in the form view.”

---

## Journey 7 — Preview “mobile”

**Today.** Desktop / Tablet / Mobile sets `max-width` 768 / 390.
No device frame, no orientation, no `@container` on the canvas.
App `@media` rules see the **browser** viewport. Per-block
breakpoint visibility is not shipped.

**The toggle can lie.** An editor who trusts “Mobile” will ship a
desktop layout squeezed into 390px.

**Change.** Chrome: **390px · Mobile preview** and
“This approximates a phone. Use the live preview link for the real
site.” Badge when `canvasStylesView` opts into `@container`.
Stage D: signed URL that renders the draft on the public layout.

---

## Journey 8 — Undo after typing

**Today.** Canvas text commits on blur (one history step). Esc
reverts locally. ⌘Z always runs **server** undo, including while
the caret is in the field — native character undo is gone.
Uncommitted keystrokes are lost when undo morphs the DOM.
Inspector typing is committed by `updatedBlockData()` before undo,
which is correct. Undo back to the saved snapshot still shows
“Unsaved changes”.

**Change.**

| Context | ⌘Z / Ctrl+Z |
|---|---|
| Caret in a field | Native / editor text undo |
| Block selected, not typing | Layout undo |
| After blur | First undo is the last committed text edit |

Keep a saved snapshot; matching it clears dirty. Optional chrome
line after undo: “Undo: removed Image from column 2”.

If the caret has uncommitted keystrokes, ask before a layout undo:
“Discard the text you are still editing?”

---

## Journey 9 — Keyboard only

**What works.** ↑/↓ select, ⇧↑/⇩ move among siblings, ⌘D, Delete,
Esc, save / undo / redo.

**What does not.** Insert a block into a chosen column. Move across
columns. Reach the hover-only block bar. Arrow-navigate the
outline. Screen-reader context (“Section selected, two columns”).
The legend shows ⌘ only.

**Change.** Insert key or ⌘K: “Add to column 2 — Paragraph, Image,
Button”. Roving tabindex: chrome → canvas blocks → inspector.
Enter on a selected block focuses the first editable. Outline:
arrows, ⌘↑/↓ reorder, announced labels. Legend: `Ctrl+Z / ⌘Z`.
Extract chrome strings into lang files when i18n starts.

---

## Journey 10 — Leave and come back

**What works.** `beforeunload` and `livewire:navigate` confirm when
`$wire.isDirty`.

**What does not.** Keystrokes not yet blurred are not dirty, so
leave can drop them with no prompt. No autosave. Remount clears
undo. No “resume”. Back goes to the resource index.

**Change.** Flush the editable before the dirty check. Interim:
`localStorage` buffer + recovery toast on mount if it differs.
Stage D: draft column, autosave every 30s and on blur.

> Leave without publishing?
> Your latest draft from 2:31 PM will be kept.
> [ Stay ] [ Leave ]

> Welcome back. Unpublished changes from today at 2:31 PM.
> [ Continue editing ] [ Discard draft ]

---

## Cross-cutting

### Selection

Two levels, as ROADMAP §3.3 already sketched:

| Level | Trigger | Visual |
|---|---|---|
| Block | Click chrome or empty area | Outline + block bar |
| Element | Click an editable | Field outline + caret; inspector scrolls to the field |
| Multi (later) | Shift-click | Combined outline; bulk style / move / delete |

Element focus must not clear block selection.

### Contextual toolbar versus inspector

| Task | Primary | Secondary |
|---|---|---|
| Type | Canvas | Inspector for a long paste |
| Image | Canvas modal | Inspector file field |
| Column layout | Section toolbar on the canvas | Inspector advanced |
| Padding / background | Floating strip | Full Style tab |
| URL, metadata | Inspector only | — |

Block bar today: handle, unicode duplicate, unicode delete.
Target: **Row & columns · Layout · Padding · Duplicate · Delete**,
all labelled.

### Outline as navigator

For pages with five or more blocks, open on Structure. Show columns
as named groups. Hidden group at the bottom. Double-click later
can rename a display label if we store one. Right-click:
Duplicate, Delete, Move to column, Copy.

### Teaching tokens

1. Never show machine keys when a label exists.
2. Language is outcome: “More space around this section.”
3. The canvas is the teacher — live update plus a short descriptor.
4. Style tab intro: “Look & spacing. Preset by your communications
   team — you will not break the layout.”
5. Soft lint later: “Light grey on white may be hard to read.”

---

## Compressed happy path (after the changes)

1. Open Design → **Start from Department homepage**.
2. Click the hero heading, type, tab to the subtext.
3. Click the hero image, upload, done.
4. Type the body paragraph in the left column.
5. Floating strip: Background → Light grey (swatch, not `surface`).
6. Mobile preview shows 390px and a honesty note.
7. **Draft saved · Publish to make live.**

Primary mode is typing on the page. Secondary is visual presets.
The sidebar is for URLs and anything a canvas control cannot say.
