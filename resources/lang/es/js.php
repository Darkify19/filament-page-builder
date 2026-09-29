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
    'confirm_unsaved' => 'Esta página tiene cambios sin guardar. ¿Salir sin guardar?',
    'confirm_delete' => '¿Eliminar este bloque? Su contenido se elimina con él.',
    'move_here' => 'Mover aquí',
    'add_here' => 'Añadir aquí',
    'place_page' => 'página',
    'place_column' => 'columna',
    'place_column_n' => 'columna :number',
    'drag_resize_columns' => 'Arrastra para redimensionar las columnas',
    'full_width' => 'Ancho completo',
    'percent_wide' => ':percent % de ancho',
    'pixels_tall' => ':height px de alto',
    'notice_style_copied' => 'Estilo copiado. Clic derecho en otro bloque → Pegar estilo.',
    'notice_copied' => 'Copiado. Pega con :keys, aquí o en otra página.',
];
