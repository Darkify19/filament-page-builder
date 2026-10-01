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
    'confirm_unsaved' => 'Cette page comporte des modifications non enregistrées. Quitter sans enregistrer ?',
    'confirm_delete' => 'Supprimer ce bloc ? Son contenu sera supprimé avec lui.',
    'move_here' => 'Déplacer ici',
    'add_here' => 'Ajouter ici',
    'place_page' => 'page',
    'place_column' => 'colonne',
    'place_column_n' => 'colonne :number',
    'drag_resize_columns' => 'Glissez pour redimensionner les colonnes',
    'full_width' => 'Pleine largeur',
    'percent_wide' => ':percent % de largeur',
    'pixels_tall' => ':height px de hauteur',
    'notice_style_copied' => 'Style copié. Clic droit sur un autre bloc → Coller le style.',
    'notice_copied' => 'Copié. Collez avec :keys, ici ou sur une autre page.',
];
