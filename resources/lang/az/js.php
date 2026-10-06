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
    'confirm_unsaved' => 'Bu səhifədə saxlanılmamış dəyişikliklər var. Yadda saxlamadan çıxmaq istəyirsiniz?',
    'confirm_delete' => 'Bu bloku silmək istəyirsiniz? Onun məzmunu da silinəcək.',
    'move_here' => 'Bura köçür',
    'add_here' => 'Bura əlavə et',
    'place_page' => 'səhifə',
    'place_column' => 'sütun',
    'place_column_n' => 'sütun :number',
    'drag_resize_columns' => 'Sütunların ölçüsünü dəyişmək üçün sürükləyin',
    'full_width' => 'Tam en',
    'percent_wide' => ':percent% enində',
    'pixels_tall' => ':height piksel hündürlüyündə',
    'notice_style_copied' => 'Üslub kopyalandı. Başqa bloka sağ klikləyin → Üslubu yapışdır.',
    'notice_copied' => 'Kopyalandı. Burada və ya başqa səhifədə :keys düymələri ilə yapışdırın.',
];
