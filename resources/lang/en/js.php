<?php

/**
 * Strings `page-builder.js` needs at runtime.
 *
 * The canvas has no build step and never will, so the translations travel as a JSON
 * payload on the editor's root element rather than through a bundler's i18n runtime.
 * `DesignPage::canvasStrings()` collects this file and `page-builder.js` reads it once in
 * `init()`; anything added here must also be read through `t()`, or the English will leak
 * into every other locale.
 */
return [
    'confirm_unsaved' => 'This page has unsaved changes. Leave without saving?',
    'confirm_delete' => 'Delete this block? Its content goes with it.',

    'move_here' => 'Move here',
    'add_here' => 'Add here',
    'place_page' => 'page',
    'place_column' => 'column',
    'place_column_n' => 'column :number',

    'drag_resize_columns' => 'Drag to resize the columns',
    'full_width' => 'Full width',
    'percent_wide' => ':percent% wide',
    'pixels_tall' => ':height px tall',

    'notice_style_copied' => 'Style copied. Right-click another block → Paste style.',
    'notice_copied' => 'Copied. Paste with :keys, here or on another page.',
];
