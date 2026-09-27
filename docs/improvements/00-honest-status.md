# 00 — Honest status

The status line at the top of `ROADMAP.md` was rewritten in the same
commit as this pack. Several ✅ marks in §§0, 3 and 4 are still stale.
This file is the correction until those sections are rewritten with
the matching work.

**Code under review:** the `dev` tree that these artifacts were written
against. The suite is **113 tests / 271 assertions**. JavaScript is
untested.

---

## Status line (proposed rewrite for `ROADMAP.md`)

> Stage 0 (Livewire tests, data-loss fixes, undo, guards) and Stage C
> **core** (flat tree, containers, token inspector, preview width
> toggle, plaintext inline edit) are shipped. Stage B is **partial**
> (text only). Stage C extras (EmbedBlock, breakpoint visibility, anchor
> UI, storage v2 wrapper, upgrade command) and Stage D / E are **not**
> shipped. Suite: 113 tests, 0 browser tests. Next: reliability, rich
> text in place, then draft / publish.

---

## What is actually shipped

### Mechanism

| Area | Reality |
|---|---|
| Canvas | Full-screen `DesignPage`: palette, outline, inspector, explicit save, dirty state |
| Storage | Bare JSON list with `id`, `type`, `data`, `parent`, `slot`, `position`, `settings`, plus passthrough keys |
| Tree | `BlockTree` hydrate / move / insert / remove / duplicate, `MAX_DEPTH = 5` |
| Registry | One `BlockRegistry` per Filament panel |
| Rendering | One Blade view per block; `@editable`; `<x-page-builder::blocks>` |
| Retired types | Kept on save; hatched placeholder on the canvas |
| Undo | Session `BlockHistory`, **limit 30** (not ~50) |
| Guards | `beforeunload` + `livewire:navigate`; delete confirm when the block has content |
| Keyboard | ⌘/Ctrl S, Z, ⇧Z, D, Del, ↑/↓, Esc — with the typing-intercept quirks in [01](01-reliability.md) |
| Style tokens | Defaults: `padding`, `background`, `width`, `align` |
| Responsive preview | `max-width` 768 / 390 on `.fpb-canvas`. Not an iframe, not `@container` |
| Inline edit | `Editable::text()` only |
| Tests | 113 Pest + Livewire. **Zero** browser / JS tests |
| Assets | Hand-written JS + CSS; Anime.js 3.2.2 vendored. **No SortableJS** |
| Plugin config that works | `blocks()`, `blocksAttribute()`, `styleTokens()`, `canvasStylesView()`, `includeLayoutBlocks()` |
| Plugin config that does nothing | `recordModel()` — stored, never read |

### Shipped primitives (six, not seven)

| `type` | Inline editables | Notes |
|---|---|---|
| `section` | none | 1–4 columns; `ratio` does not follow `columns` |
| `text` | `body` (plaintext, multiline) | ROADMAP calls this rich text. It is not. |
| `image` | `alt` only | Stored path printed raw as `src` — broken after save |
| `button` | `label` only | `url` is inspector-only; `javascript:` passes |
| `spacer` | none | `data-fpb-height` |
| `divider` | none | Empty schema |

**Not shipped:** `EmbedBlock`, `CustomHtmlBlock`, heading, quote, video,
gallery, accordion, tabs, HTML.

### Editable kinds

| Kind | Status |
|---|---|
| `Editable::text()` | Wired end to end |
| `Editable::richText()` | Declared. Emits `data-fpb-kind`. No `contenteditable`, no TipTap |
| `image` / `link` / `list` | ROADMAP only. No factories, no JS |

---

## ROADMAP claims that are false

| Claim | Where | Reality |
|---|---|---|
| EmbedBlock shipped | §4.2 ✅ | Does not exist |
| TextBlock is a rich-text primitive | §4.2 | Plaintext `Editable::text()` + `Textarea` |
| Per-breakpoint visibility + anchor id | §4.3 ✅ | No UI, no schema, no emission |
| Storage v2 wrapper `{"version":2,"blocks":[…]}` | §4.1 ✅ | Column is still a bare list |
| `pages:upgrade-blocks` command | §4.1 | No artisan commands in the package |
| SortableJS vendored for nesting | §6 | Native HTML5 DnD only |
| Pest browser tests for drag / drop / typing | §0.1 ✅ | None in `tests/` |
| Undo capped at ~50 | §0.6 | `BlockHistory::LIMIT = 30` |
| Suite is at 105 tests | status line | 113 |
| `recordModel()` honoured | §0.3 ✅ | Stored, unread |
| `CustomHtmlBlock` escape hatch | §4.3 | Docs example only |
| `filament:optimize` publishes assets | `docs/index.html` | Only `filament:assets` / `filament:upgrade` |
| `.fpb-toolbar` is an overridable class | `docs/index.html` | Does not exist. Use `.fpb-chrome` |

`docs/index.html` is more honest than the roadmap on draft / publish
and rich text, and should stay the public source of limitations.

---

## Stage scorecard

| Stage | Claimed | Actual |
|---|---|---|
| **0 — foundation** | Done | Done for Livewire tests, unknown-type preservation, `blocksAttribute()`, per-panel registry, undo, guards, keyboard. **Not** done: browser tests, `recordModel()` |
| **B — inline editing** | Text done; rich text next | Text done. richText / image / link / list missing |
| **C — structure and style** | Done | Core done (tree, section, tokens, width toggle). Extras (embed, visibility, anchor, v2 wrapper) not done |
| **D — workflow** | Next | Not started |
| **E — free canvas** | On the table | Recommend defer / drop unless a poster page is required |

---

## Usable today / not ready yet

**Usable today for:** nested columns, plaintext microcopy on the canvas,
token spacing and background, prototyping a layout beside the form
editor (with the caveats in [01](01-reliability.md)).

**Not ready for:** confident live editing without a draft column,
media-heavy pages, touch editing, or two people on the same page.

---

## Docs that must move with the next code change

When the matching work lands, update these in the **same commit**:

- `ROADMAP.md` status line and the ✅ marks in §§0, 3, 4
- `docs/index.html` Known limitations
- `README.md` “Still to come”
- This file’s scorecard
