<?php

/**
 * Strings `page-builder.js` needs at runtime.
 *
 * A translation of resources/lang/en/js.php. The canvas has no build step, so these travel as
 * a JSON payload on the editor's root element: `DesignPage::canvasStrings()` collects this
 * file and the script reads it once in `init()`. Anything added here must be read through
 * `t()` or the English leaks into every other locale.
 */
return [
    'confirm_unsaved' => 'Diese Seite hat ungespeicherte Änderungen. Ohne Speichern verlassen?',
    'confirm_delete' => 'Diesen Block löschen? Sein Inhalt wird mitgelöscht.',
    'move_here' => 'Hierher verschieben',
    'add_here' => 'Hier hinzufügen',
    'place_page' => 'Seite',
    'place_column' => 'Spalte',
    'place_column_n' => 'Spalte :number',
    'drag_resize_columns' => 'Zum Skalieren der Spalten ziehen',
    'full_width' => 'Volle Breite',
    'percent_wide' => ':percent % breit',
    'pixels_tall' => ':height px hoch',
    'notice_style_copied' => 'Stil kopiert. Anderen Block rechtsklicken → Stil einfügen.',
    'notice_copied' => 'Kopiert. Mit :keys hier oder auf einer anderen Seite einfügen.',
];
