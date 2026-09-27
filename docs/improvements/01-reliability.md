# 01 — Reliability

Bugs, data-loss paths, and security gaps that should land **before**
new features. Each item names the symptom an editor sees, the mechanism,
severity, and whether it blocks a named future feature.

The canvas mutation path is already well tested. The risk clusters
around the form-editor bridge, shipped primitives, session history, and
an untested JavaScript surface.

---

## Fix these first

Twelve items, in this order. The sequence is chosen so Stage B (rich
text, image, link) and Stage D (draft / publish) do not land on a
broken foundation.

| # | Item | Severity | Unblocks |
|---|---|---|---|
| 1 | Remint colliding ids on hydrate; warn on mount | Critical | Form/canvas coexistence, copy/paste, templates |
| 2 | Optimistic lock on `save()` | Critical | Draft / publish, two-tab editing |
| 3 | ImageBlock public URL | High | Stage B image picker |
| 4 | ButtonBlock URL allow-list | High | Stage B `Editable::link()` |
| 5 | Duplicate checks every descendant’s `isVisible()` | High | Restricted blocks, reusable sections |
| 6 | Sanitise or refuse `setBlockField` for `richText` | High | Stage B rich text |
| 7 | Browser smoke tests (drop, blur-commit, save) | High | Every Stage B JS change |
| 8 | Couple section `ratio` to `columns` | Medium | Layout trust, templates |
| 9 | `.fpb-text` `white-space: pre-line` + ⌘S / ⌘Z | Medium | Inline text, then rich text |
| 10 | History: session driver + dirty-vs-saved snapshot | Medium | Production undo, Stage D dirty semantics |
| 11 | Validate parent is a container and the slot exists; surface orphans | Medium | Hidden-slot ghosts, form-editor orphans |
| 12 | `try/finally` around block render; move Pest helpers out of `DesignPageTest.php` | Medium | Octane, developer velocity |

---

## Catalog

### A. Shipped primitives

#### A1. ImageBlock prints a disk path as `src`

**Symptom.** The image appears during upload (Livewire temp URL), then
breaks after save: `<img src="pages/foo.jpg">`.

**Cause.** `resources/views/components/image.blade.php` echoes
`$data['src']` raw. Stored value is a path on the `public` disk.

**Fix.** Resolve `Storage::disk('public')->url(...)` in the view (the
docs’ Hero example already does this). Add a render test with a stored
path.

**Blocks.** Stage B image picker.

#### A2. Section `ratio` does not follow `columns`

**Symptom.** Columns = 3, ratio still `1-1` → two-track grid; the third
column wraps onto a new row.

**Cause.** `SectionBlock` seeds both independently. The view prefers
the stored ratio over a column-derived fallback.

**Fix.** One control that writes both, or ignore an incompatible ratio.
Warn before shrinking columns that still hold children.

**Blocks.** Templates and reusable sections.

#### A3. Multiline text collapses after blur

**Symptom.** Line breaks typed on the canvas disappear after the
re-render, then get written back collapsed.

**Cause.** JS commits `innerText`. `.fpb-text` has no
`white-space: pre-line`.

**Fix.** Add the rule on canvas and public CSS.

**Blocks.** The multiline `TextBlock` that already ships.

#### A4. Button `url` accepts `javascript:`

**Symptom.** A crafted or careless URL becomes `href="javascript:…"` on
the public page. `ButtonBlock::isVisible()` is always true.

**Cause.** `TextInput` with `maxLength` only.

**Fix.** Allow `http`, `https`, `mailto`, and relative paths. Reject
`javascript:` and `data:`.

**Blocks.** `Editable::link()`.

---

### B. Data loss

#### B1. Form-editor clone → duplicate id → silent delete

**Symptom.** Clone a block in Filament’s `Builder`. The clone never
renders. The next canvas save removes it from the database.

**Cause.** The Builder copies the item including `id`.
`BlockTree::flatten()` skips the second occurrence of an id.
`save()` writes the shortened array.

**Severity.** Critical.

**Fix.** Remint colliding ids inside `hydrate()`, and/or refuse Builder
clone for nested pages. Warn on mount when duplicates were repaired.

#### B2. Form-editor delete of a section orphans its children

**Symptom.** Children stay in JSON, invisible on every surface, and
cannot be selected or deleted from the canvas.

**Cause.** The Builder has no tree. `flatten()` appends orphans.

**Fix.** Mount-time orphan report. A “repair tree” action. Discourage
the form editor once a page is nested (see [03](03-editor-journeys.md)
Journey 6).

#### B3. Form-editor reorder is ignored; new items lack tree keys

**Symptom.** Reordering in the form does nothing after the first canvas
save (`position` wins). New items become roots.

**Fix.** Document; optional roots-only form mode.

#### B4. Hidden-slot ghosts

**Symptom.** Drop a section from 3 columns to 2. `col-2` children
vanish. They reappear if the count goes back up. Deleting the
“empty” section still asks for confirmation, because
`blockHasContent()` counts every child.

**Fix.** Warn on shrink. Offer move-to-last-column or delete. Badge
hidden blocks on the section bar and in the outline.

#### B5. Unstable ids until the first canvas save

**Symptom.** Missing ids are minted on every `hydrate()` call, so they
change across requests until `save()` persists them.

**Fix.** Mint at write boundaries. Document that anchors and caches
must not key off an unseen id.

**Blocks.** Anchor id, reusable sections by reference.

#### B6. `HasBlocks::ensureBlockIds()` spread-order bug

**Symptom.** An explicit `'id' => null` stays `null`. Nothing in the
package calls this method today.

**Fix.** Spread first, then id — same order as `hydrate()`. Add a unit
test.

#### B7. Last writer wins

**Symptom.** Canvas save after a form save, or two tabs, silently
overwrites the other.

**Cause.** `DesignPage::save()` assigns `$this->blocks` and calls
`$record->save()` with no version check.

**Severity.** Critical for two surfaces; high for two tabs.

**Fix.** Compare `updated_at` or a `blocks_version` column. Conflict
UI. Foundational for draft / publish.

---

### C. Authorisation and security

#### C1. `duplicateBlock()` checks only the root type

**Symptom.** Duplicating a visible section clones a restricted child
the user could not insert.

**Fix.** Walk the subtree. Refuse or strip non-`isVisible()`
descendants. Test with a nested `RestrictedBlock`.

#### C2. `insert` / `move` do not check container or slot

**Symptom.** A crafted Livewire call nests a child under a leaf, or
into a slot the parent does not expose — a hidden ghost.

**Fix.** Validate in `DesignPage` with `registry()->isContainer()` and
`registry()->slots($type, $data)`.

#### C3. `richText` accepts any string

**Symptom.** `setBlockField()` will store arbitrary HTML for a
`richText` field. A block that renders `{!! !!}` becomes an XSS path
the moment any type declares `richText`.

**Fix.** Sanitise on the inline path, or refuse inline writes until
Filament’s TipTap bundle is mounted. **Do not ship in-place rich text
without this.**

#### C4. `move` / `remove` are ungated on purpose

An editor may reorder or delete a type they cannot author. Documented.
Leave unless the product changes that policy. Removing a section still
takes restricted descendants with it.

---

### D. Canvas JavaScript

#### D1. ⌘S while the caret is in a field saves without that edit

Save is handled *before* `isTyping()`. Commit is blur-driven. The
toolbar button is safe (click blurs first). The shortcut is not.

**Fix.** Blur / flush the active editable, then save.

#### D2. ⌘Z intercepts native text undo

Undo is also handled before `isTyping()`. Inspector inputs and
contenteditables lose character undo. Commit `c76513c` claimed the
opposite.

**Fix.** Ignore layout undo / redo while typing. TipTap will need the
same rule.

#### D3. Responsive preview is a squeeze

`data-preview` only sets `max-width`. `@media` rules see the browser
viewport, not the canvas. The package sets no `container-type`.

**Fix.** Label the toggle honestly (“narrow width”), set
`container-type: inline-size` on `.fpb-canvas`, and tell apps to use
`@container`. Do not iframe — that breaks drag and Livewire.

#### D4. No JavaScript tests

Renaming a `$wire` method, a `data-*` attribute, or a `$root` lookup
breaks drag and inline editing silently. `scripts/check-drift.sh` is
the only contract check.

**Fix.** Pest browser smokes: one drop, one blur-commit, one save.
Run them in CI. Keep the drift script.

---

### E. History and session

#### E1. Undo back to the saved state still reads “Unsaved changes”

`travel()` always sets `isDirty = true`. Nothing snapshots the last
successful `save()`.

**Fix.** Keep a saved snapshot; clear dirty when `$blocks` matches it.

#### E2. Cookie session + 30 snapshots

Thirty copies of a large page in a cookie session overflow the
browser. The canvas needs a server-side session driver (file,
database, redis).

**Fix.** Document the requirement. Compress, or store history
server-side keyed by record + user, if cookie sessions must be
supported.

#### E3. Second tab wipes the first tab’s undo

`mount()` calls `clear()`. One session stack, tagged by Livewire
component id.

**Fix.** Per-record key, or a warning. Low priority next to E1 / E2.

---

### F. Rendering and runtime

#### F1. Exception inside a block view leaves the context stacked

No `try/finally` around the include. Under Octane or a queue worker
the next render can emit `contenteditable` on a public page.

**Fix.** Wrap every push in try / finally; call `idle()` at the public
entry.

#### F2. Retired container hides its public subtree

Unknown types are skipped, children included. Data is kept. Document;
optional legacy wrapper later.

#### F3. Any array with `type === 'doc'` becomes HTML

`BlockStateNormaliser` is shape-based. Restrict to declared rich-text
field names when app data can look like a TipTap document.

#### F4. Invalid inspector commit can store a temp upload URL

The validation fallback restores **top-level** file fields only.
Nested (repeater) uploads in an invalid block can persist a dead
temporary URL.

---

### G. API and docs

| Id | Issue | Severity |
|---|---|---|
| G1 | `recordModel()` is a no-op | Low — wire it or remove it |
| G2 | Assets load on every panel page; `dev-main` version string does not bust caches | Medium |
| G3 | Public CSS is not auto-enqueued | Medium — document the link |
| G4 | Integer-key token maps never validate; token names like `field` collide with `data-fpb-field` | Low |
| G5 | `blockHasContent()` counts hidden-slot children | Low |
| G6 | `pointer-events: none` on `.fpb-block-body` — links and widgets are inert on the canvas | Low, by design |

---

### H. Test harness

| Id | Issue |
|---|---|
| H1 | `require_once DesignPageTest.php` — running `NestingTest.php` alone registers 31 extra tests under the wrong `beforeEach` |
| H2 | No tests for A1–A4, C1, C2, B1, B7, E1, `ensureBlockIds()` |
| H3 | ROADMAP / docs false claims (see [00](00-honest-status.md)) |
| H4 | `dev → main` auto-merge does not check CI itself |

Move shared helpers into `tests/Pest.php` (or a file it requires). Do
not extend the `require_once` pattern.

---

## What is in good shape

Do not “fix” these; they are load-bearing.

- `BlockTree` cycle / depth refusal (`===` means no-op)
- Inspector rules: `getState()`, drop `cacheSchema` before `fill()`,
  never commit inside `syncInspector()`
- Retired types preserved through save (`83310f3`)
- `setBlockField()` trusts the declaration, not `@editable`
- Public render push / pop (`dc08dbc`) is tested
- `scripts/check-drift.sh` exists — run it, and add it to CI if it
  is not already there

---

## Mapping to future work

| Future work | Weakened by |
|---|---|
| Rich text in place | C3, D2, D4, D1 |
| Image picker | A1, F4 |
| Link editable | A4 |
| Draft / publish | B7, E1, E2 |
| Revisions | B2, B4, B7 |
| Reusable sections / templates | B1, B5, A2, C1 |
| Copy / paste between pages | B1, B5 |
| Form-editor bridge | B1, B2, B3, B7 |

Until B1 / B2 / B3 / B7 are addressed, **do not treat the Filament
Builder form editor as safe for a nested page.**
