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
    'confirm_unsaved' => 'Bu sayfada kaydedilmemiş değişiklikler var. Kaydetmeden ayrılınsın mı?',
    'confirm_delete' => 'Bu blok silinsin mi? İçeriği de silinir.',
    'move_here' => 'Buraya taşı',
    'add_here' => 'Buraya ekle',
    'place_page' => 'sayfa',
    'place_column' => 'sütun',
    'place_column_n' => ':number. sütun',
    'drag_resize_columns' => 'Sütunları boyutlandırmak için sürükle',
    'full_width' => 'Tam genişlik',
    'percent_wide' => '%:percent genişlik',
    'pixels_tall' => ':height px yükseklik',
    'notice_style_copied' => 'Stil kopyalandı. Başka bir bloğa sağ tıkla → Stili yapıştır.',
    'notice_copied' => 'Kopyalandı. :keys ile buraya ya da başka bir sayfaya yapıştır.',
];
