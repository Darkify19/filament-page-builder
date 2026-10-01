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
    'confirm_unsaved' => 'На этой странице есть несохранённые изменения. Выйти без сохранения?',
    'confirm_delete' => 'Удалить этот блок? Его содержимое будет удалено вместе с ним.',
    'move_here' => 'Переместить сюда',
    'add_here' => 'Добавить сюда',
    'place_page' => 'страница',
    'place_column' => 'колонка',
    'place_column_n' => 'колонка :number',
    'drag_resize_columns' => 'Перетащите, чтобы изменить ширину колонок',
    'full_width' => 'Во всю ширину',
    'percent_wide' => ':percent% ширины',
    'pixels_tall' => 'высота :height px',
    'notice_style_copied' => 'Стиль скопирован. Щёлкните правой кнопкой по другому блоку → Вставить стиль.',
    'notice_copied' => 'Скопировано. Вставьте через :keys — здесь или на другой странице.',
];
