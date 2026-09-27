# 00 — Honest status

Updated in the same commit as the reliability / Now implementation.
JavaScript is still untested.

---

## Status line (for `ROADMAP.md`)

> Stage 0 (Livewire tests, data-loss fixes, undo, guards) and Stage C
> **core** are shipped. The reliability queue and the Now items Embed /
> anchor / palette search / ghost recovery / optimistic lock are in.
> Stage B is still plaintext only. Not shipped: browser tests, draft /
> publish, per-breakpoint visibility, a versioned storage wrapper.

---

## What is actually shipped

### Mechanism

| Area | Reality |
|---|---|
| Canvas | Full-screen `DesignPage`: palette (search + descriptions), outline, inspector, breadcrumbs, token picks, explicit save, dirty-vs-saved snapshot |
| Storage | Bare JSON list with `id`, `type`, `data`, `parent`, `slot`, `position`, `settings`, plus passthrough keys (`anchor`) |
| Tree | `BlockTree` hydrate remints colliding ids; `ghosts()`; `MAX_DEPTH = 5` |
| Registry | One `BlockRegistry` per Filament panel; optional `description()` |
| Rendering | One Blade view per block; `@editable`; `<x-page-builder::blocks>`; `PageBuilder::renderSafely()` |
| Retired types | Kept on save; hatched placeholder on the canvas |
| Undo | Session `BlockHistory`, **limit 30**. Dirty clears when the tree matches `savedBlocks` |
| Guards | `beforeunload` + `livewire:navigate`; delete confirm; optimistic `updated_at` lock |
| Keyboard | ⌘/Ctrl S flushes the active editable first; Z / ⇧Z ignored while typing |
| Style tokens | Defaults: `padding`, `background`, `width`, `align`. Visual picks, not only a select |
| Responsive preview | `max-width` 768 / 390 on `.fpb-canvas` plus `container-type: inline-size` |
| Inline edit | `Editable::text()` only. `richText` is refused on `setBlockField` |
| Tests | Pest + Livewire. **Zero** browser / JS tests. Shared helpers live in `tests/Support/helpers.php` |
| Assets | Hand-written JS + CSS; Anime.js 3.2.2 vendored. **No SortableJS** |
| Plugin config that works | `blocks()`, `blocksAttribute()`, `styleTokens()`, `canvasStylesView()`, `includeLayoutBlocks()` |
| Plugin config that does nothing | `recordModel()` — stored, never read |

### Shipped primitives (seven)

| `type` | Inline editables | Notes |
|---|---|---|
| `section` | none | 1–4 columns; `ratioFor()` keeps the grid on that many tracks |
| `text` | `body` (plaintext, multiline) | `.fpb-text` is `white-space: pre-line` |
| `image` | `alt` only | `MediaUrl` resolves a public-disk path at render |
| `button` | `label` only | `SafeUrl` allow-list on `href` |
| `embed` | none | Allowlisted YouTube / Vimeo / Maps iframe |
| `spacer` | none | `data-fpb-height` |
| `divider` | none | Empty schema |

**Not shipped:** `CustomHtmlBlock`, heading, quote, gallery, accordion, tabs.

### Editable kinds

| Kind | Status |
|---|---|
| `Editable::text()` | Wired end to end |
| `Editable::richText()` | Declared. Inspector only. Inline writes refused |
| `image` / `link` / `list` | ROADMAP only. No factories, no JS |

---

## ROADMAP claims that remain false

| Claim | Where | Reality |
|---|---|---|
| TextBlock is a rich-text primitive | §4.2 | Plaintext `Editable::text()` + `Textarea` |
| Per-breakpoint visibility | §4.3 ✅ | No UI, no schema |
| Storage v2 wrapper `{"version":2,"blocks":[…]}` | §4.1 | Column is still a bare list |
| `pages:upgrade-blocks` command | §4.1 | No artisan commands in the package |
| SortableJS vendored for nesting | §6 | Native HTML5 DnD only |
| Pest browser tests for drag / drop / typing | §0.1 | None in `tests/` |
| `recordModel()` honoured | §0.3 | Stored, unread |
| `CustomHtmlBlock` escape hatch | §4.3 | Docs example only |

Fixed in this pass: EmbedBlock, anchor id, undo cap documented as 30,
`.fpb-chrome` / `filament:assets`, image URL, button URL, section ratio.

---

## Stage scorecard

| Stage | Claimed | Actual |
|---|---|---|
| **0 — foundation** | Done | Done for Livewire tests, unknown-type preservation, `blocksAttribute()`, per-panel registry, undo, guards, keyboard. **Not** done: browser tests, wiring `recordModel()` |
| **Reliability** | Planned | Done (except browser smokes and a cookie-session store) |
| **B — inline editing** | Text done; rich text next | Text done. richText / image / link / list still missing |
| **C — structure and style** | Done | Core + Embed + anchor done. Visibility and v2 wrapper not done |
| **D — workflow** | Next | Optimistic lock only. Draft / publish not started |
| **E — free canvas** | On the table | Recommend defer / drop unless a poster page is required |

---

## Usable today / not ready yet

**Usable today for:** nested columns, plaintext microcopy on the canvas,
token spacing and background, embeds, in-page anchors, repairing
form-editor orphans from the Hidden list.

**Not ready for:** confident live editing without a draft column,
in-place rich text, or two people on the same page (the lock refuses
the second save; it does not merge).
