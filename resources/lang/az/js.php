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
    'confirm_unsaved' => 'Bu səhifədə yadda saxlanılmamış dəyişikliklər var. Saxlamadan çıxılsın?',
    'confirm_delete' => 'Bu blok silinsin? Məzmunu da silinəcək.',
    'move_here' => 'Bura köçür',
    'add_here' => 'Bura əlavə et',
    'place_page' => 'səhifə',
    'place_column' => 'sütun',
    'place_column_n' => ':number. sütun',
    'drag_resize_columns' => 'Sütunları ölçüləndirmək üçün sürüşdürün',
    'full_width' => 'Tam en',
    'percent_wide' => '%:percent en',
    'pixels_tall' => ':height px hündürlük',
    'notice_style_copied' => 'Stil kopyalandı. Başqa bir bloka sağ klikləyin → Stili yapışdır.',
    'notice_copied' => 'Kopyalandı. :keys ilə bura və ya başqa səhifəyə yapışdırın.',
];
