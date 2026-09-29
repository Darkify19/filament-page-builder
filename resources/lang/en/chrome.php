<?php

/**
 * The editor shell: toolbar, palette, outline, inspector, context menu, preview.
 *
 * English here is the source of truth. Every other locale is a translation of this file
 * and the test suite fails when one of them drifts behind, because a missing key does not
 * fall back — it renders as the raw dotted path on a screen an editor is looking at.
 *
 * `:name` placeholders are replaced with `:key` or `:key, ['name' => …]` from the caller.
 * Keep them: an aria-label that reads "Move Heading" is useful, "Move " is not.
 */
return [
    /* ── Toolbar ─────────────────────────────────────────── */

    'back_to' => 'Back to :resource',
    'design_title' => 'Design: :title',
    'draft_not_saved' => 'Draft not saved',
    'saved' => 'Saved',
    'undo' => 'Undo',
    'redo' => 'Redo',
    'form_editor' => 'Form editor',
    'form_editor_columns_warning' => 'This page uses columns. The form view cannot show that layout correctly and may delete or duplicate content. Open anyway?',
    'preview' => 'Preview',
    'preview_hint' => 'See the page as visitors will, unsaved changes included',
    'preview_note' => 'Unsaved changes included. Links, video and custom code are live.',
    'preview_width' => 'Preview width',
    'refresh' => 'Refresh',
    'back_to_editing' => 'Back to editing',
    'save_layout' => 'Save layout',
    'rendering' => 'Rendering the page…',
    'layout_saved' => 'Layout saved',

    /* ── Preview widths ──────────────────────────────────── */

    'desktop' => 'Desktop',
    'tablet' => 'Tablet',
    'mobile' => 'Mobile',

    /* ── Palette ─────────────────────────────────────────── */

    'blocks' => 'Blocks',
    'structure' => 'Structure',
    'search_blocks' => 'Search blocks',
    'palette_hint_selected' => 'Click to add after the selection, or drag onto a column.',
    'palette_hint_empty' => 'Click to add at the end of the page, or drag onto the canvas.',
    'palette_hint_static' => 'Drag onto the page or into a column. Click to insert at the selection.',
    'add_block' => 'Add :label',

    /**
     * Palette group headings. A block may declare any category it likes; one this package
     * does not know about falls back to the application's own label rather than a key.
     */
    'layout' => 'Layout',
    'content' => 'Content',
    'media' => 'Media',
    'design' => 'Design',
    'developer' => 'Developer',

    /* ── Outline and ghosts ──────────────────────────────── */

    'document' => 'Document',
    'structure_hint' => 'Click a block to select it. The outline follows the page.',
    'no_blocks_yet' => 'This page has no blocks yet.',
    'hidden' => 'Hidden (:count)',
    'ghosts_hint' => 'Stored on the page but not shown — an orphaned parent or a removed column.',
    'ghost_orphan' => 'Move to page',
    'ghost_hidden_slot' => 'Move to last column',

    /* ── Empty states ────────────────────────────────────── */

    'start_layout' => 'Start a layout',
    'empty_wide' => 'Add a row of columns, or drop a block from the left.',
    'empty_narrow' => 'Add a row of columns, or tap Blocks to pick one.',
    'nothing_selected' => 'Nothing selected',
    'inspector_empty_wide' => 'Click a block on the page to edit its content, style and layout. Right-click for more.',
    'inspector_empty_narrow' => 'Select a block on the Page tab, then come back here to edit it.',

    /* ── Inspector ───────────────────────────────────────── */

    'content' => 'Content',
    'style' => 'Style',
    'layout' => 'Layout',
    'page' => 'Page',
    'settings' => 'Settings',
    'inside' => '↑ Inside :parent',
    'narrower' => 'Make the panel narrower',
    'wider' => 'Make the panel wider',
    'close' => 'Close (Esc)',
    'unregistered_hint' => "This block's type is no longer registered, so there are no fields to show. Its stored content is preserved. You can still move or remove it.",
    'no_permission_hint' => 'You do not have permission to edit this block’s content. You can still move or remove it.',
    'style_permission_hint' => 'Only people who may edit this block can restyle it.',
    'brand_presets' => 'Brand presets',
    'brand_presets_hint' => 'Spacing and colours from your organisation’s style guide.',
    'default' => 'Default',
    'anchor' => 'Anchor',
    'anchor_hint' => 'Link to this block with :anchor.',
    'anchor_example' => '#intro',

    /**
     * Labels for the preset buttons in Brand presets.
     *
     * Only the ones with no counterpart elsewhere. The spacing scale (none/small/medium/
     * large/extra large) is shared with the section gap and spacer height options, so it
     * lives in `blocks.common` and a translator fixes it once.
     */
    'tokens' => [
        'surface' => 'Surface',
        'muted' => 'Muted',
        'contrast' => 'Contrast',
        'narrow' => 'Narrow',
        'wide' => 'Wide',
        'full' => 'Full',
        'start' => 'Start',
        'end' => 'End',
    ],

    /* ── Keyboard shortcuts ──────────────────────────────── */

    'shortcut_save' => 'Save',
    'shortcut_undo' => 'Undo',
    'shortcut_redo' => 'Redo',
    'shortcut_copy_paste' => 'Copy, paste',
    'shortcut_duplicate' => 'Duplicate',
    'shortcut_move' => 'Move block',
    'shortcut_select' => 'Select',
    'shortcut_delete' => 'Delete',
    'shortcut_deselect' => 'Deselect',
    'shortcut_more_actions' => 'More actions',

    /* ── Context menu ────────────────────────────────────── */

    'edit_content' => 'Edit content',
    'spacing_and_size' => 'Spacing and size',
    'select_parent' => 'Select parent',
    'copy' => 'Copy',
    'paste_after' => 'Paste after',
    'paste_inside' => 'Paste inside',
    'copy_style' => 'Copy style',
    'paste_style' => 'Paste style',
    'add_block_below' => 'Add a block below…',
    'duplicate' => 'Duplicate',
    'move_up' => 'Move up',
    'move_down' => 'Move down',
    'reset_style' => 'Reset style',
    'delete' => 'Delete',

    /* ── The block bar on the canvas ─────────────────────── */

    'drag_to_move' => 'Drag to move',
    'move' => 'Move :label',
    'align_text' => 'Align text',
    'align' => 'Align :side',
    'edit' => 'Edit',
    'edit_named' => 'Edit :label',
    'move_named_up' => 'Move :label up',
    'move_named_down' => 'Move :label down',
    'duplicate_named' => 'Duplicate :label',
    'more_actions' => 'More actions (right-click)',
    'more_actions_named' => 'More actions for :label',
    'delete_named' => 'Delete :label',
    'retired' => 'This page holds a :type block, which this site no longer offers. Its content is kept and saved untouched; it cannot be shown or edited here.',
    'resize_width' => 'Drag to change the width. Double-click to reset.',
    'resize_height' => 'Drag to change the height. Double-click to reset.',

    /* ── Alignment sides, for the quick buttons ──────────── */

    'left' => 'Left',
    'centre' => 'Centre',
    'right' => 'Right',
    'justify' => 'Justify',

    /* ── Notifications ───────────────────────────────────── */

    'duplicate_ids_repaired' => 'Repaired duplicate block ids. Save to keep both copies.',
    'saved_elsewhere' => 'This page was saved elsewhere. Reload to avoid overwriting those changes.',

    /* ── Accessible names for landmarks ──────────────────── */

    'aria_selected_block' => 'Selected block',
    'aria_canvas_width' => 'Canvas width',
    'aria_blocks_and_outline' => 'Blocks and outline',
    'aria_block_inspector' => 'Block inspector',
    'aria_design_workspace' => 'Design workspace',
    'aria_block_actions' => 'Block actions',
    'aria_add_block' => 'Add a block',
    'aria_page_preview' => 'Page preview',
];
