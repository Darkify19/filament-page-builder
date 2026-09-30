<?php

/**
 * Every string a block contributes to the palette, the outline and its own form.
 *
 * Block definitions are static, so a label here is looked up at request time — which means
 * `label()` is not a constant any more and anything that used it to cache the string must
 * call it again after a locale switch. That is the deal for a block library that speaks
 * the editor's language.
 *
 * `common` holds labels shared by two or more blocks (a video and an embed both ask for a
 * start time). Translators see each of them once, and a block section points at it rather
 * than repeating the English.
 */
return [
    'common' => [
        'auto' => 'Auto',
        'from' => 'From',
        'to' => 'To',
        'type' => 'Type',
        'linear' => 'Linear',
        'radial' => 'Radial',
        'direction' => 'Direction',
        'strength' => 'Strength',
        'start_at' => 'Start at',
        'stop_at' => 'Stop at',
        'end_placeholder' => 'End',
        'time_hint' => 'Seconds or m:ss',
        'time_invalid' => 'Use seconds (90) or minutes and seconds (1:30).',
        'autoplay' => 'Autoplay',
        'autoplay_hint' => 'Plays muted',
        'loop' => 'Loop',
        'muted' => 'Muted',
        'shape' => 'Shape',
        'height' => 'Height',
        'none' => 'None',
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'extra_large' => 'Extra large',
    ],

    'section' => [
        'label' => 'Section',
        'description' => 'A row of columns. Drop other blocks into a column.',
        'columns' => 'Columns',
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
        'four' => 'Four',
        'ratio' => 'Column layout',
        'ratio_hint' => 'Or drag the edge between two columns on the page.',
        'full' => 'Full',
        'custom_ratio' => 'Custom (:percent)',
        'gap' => 'Space between',
        'valign' => 'Line up columns',
        'top' => 'Top',
        'middle' => 'Middle',
        'bottom' => 'Bottom',
        'same_height' => 'Same height',
        'stack' => 'Stack the columns',
        'stack_tablet' => 'On tablets and phones',
        'stack_never' => 'Never',
    ],

    'heading' => [
        'label' => 'Heading',
        'description' => 'A title for the page or a section, H1 to H6.',
        'placeholder' => 'Write a heading',
        'text' => 'Heading',
        'level' => 'Level',
    ],

    'text' => [
        'label' => 'Text',
        'description' => 'A paragraph you can type into on the page.',
        'placeholder' => 'Write something',
    ],

    'button' => [
        'label' => 'Button',
        'description' => 'A link that looks like a button.',
        'placeholder' => 'Button label',
        'link' => 'Link',
        'link_invalid' => 'Enter an http(s), mailto, tel, hash or same-site path. javascript: and data: links are not allowed.',
    ],

    'image' => [
        'label' => 'Image',
        'description' => 'A photograph, optionally with a gradient laid over it.',
        'canvas_placeholder' => 'Choose an image in the sidebar',
        'alt_placeholder' => 'Describe the image',
        'src' => 'Image',
        'alt' => 'Alt text',
        'overlay' => 'Gradient overlay',
        'overlay_toggle' => 'Lay a gradient over the image',
    ],

    'list' => [
        'label' => 'List',
        'description' => 'Bulleted or numbered points, one per line.',
        'canvas_placeholder' => 'Add the list’s items in the sidebar, one per line.',
        'items' => 'Items',
        'items_hint' => 'One item per line.',
        'marker' => 'Marker',
        'bullets' => 'Bullets',
        'numbers' => 'Numbers',
        'ticks' => 'Ticks',
    ],

    'quote' => [
        'label' => 'Quote',
        'description' => 'A quotation, with who said it.',
        'placeholder' => 'Write the quotation',
        'cite_placeholder' => 'Who said it',
        'text' => 'Quotation',
        'cite' => 'Attribution',
    ],

    'embed' => [
        'label' => 'Embed',
        'description' => 'YouTube, Vimeo, Maps, Google Forms and Slides, Spotify, Canva…',
        'canvas_autoplay_note' => 'Autoplay is paused while you edit. It plays on the published page.',
        'url' => 'Link or embed code',
        'url_placeholder' => 'https://www.youtube.com/watch?v=…',
        'url_hint' => 'Paste the page address, a share link, or the whole embed code.',
        'fixed_height' => 'Fixed height',
        'title' => 'Title for screen readers',
        'title_placeholder' => 'Embedded content',
        'hide_controls' => 'Hide controls',
        'ratio_video' => '16:9 (video)',
        'ratio_square' => 'Square',
        'ratio_form' => '3:4 (form, document)',
        'ratio_vertical' => '9:16 (vertical video)',
        'ratio_cinema' => '21:9 (cinema)',
    ],

    'video' => [
        'label' => 'Video',
        'description' => 'An uploaded video with start time, autoplay and loop.',
        'canvas_placeholder' => 'Upload a video, or paste a link to a video file, in the sidebar.',
        'src' => 'Video file',
        'url' => '…or a link to a video file',
        'url_placeholder' => 'https://example.com/clip.mp4',
        'url_hint' => 'For YouTube or Vimeo, use the Embed block.',
        'url_invalid' => 'Enter an http(s) link to an .mp4 or .webm file.',
        'poster' => 'Cover image',
        'controls' => 'Show controls',
        'ratio_natural' => 'Natural',
    ],

    'code' => [
        'label' => 'Custom code',
        'description' => 'Your own HTML, CSS and JavaScript — for animations and custom designs.',
        'tabs' => 'Code',
        'tab_html' => 'HTML',
        'tab_css' => 'CSS',
        'tab_js' => 'JavaScript',
        'html_hint' => 'Shortcodes work here too, e.g. [year].',
        'css_hint' => 'Applies to the whole page. Start your selectors with a class of your own.',
        'js_hint' => 'Runs once the page has loaded, in Preview and on the published page — not on the canvas. `root` is this block’s element.',
    ],

    'divider' => [
        'label' => 'Divider',
        'description' => 'A horizontal rule.',
    ],

    'spacer' => [
        'label' => 'Spacer',
        'description' => 'Empty space between blocks. Drag its bottom edge to resize.',
        'canvas_label' => 'Space',
        'height' => 'Height',
        'size' => 'Exact height',
        'size_placeholder' => 'Use the preset',
        'size_hint' => 'Or drag the bottom edge of the spacer on the page.',
    ],

    'shortcode' => [
        'label' => 'Shortcode',
        'description' => 'Live data from the site, e.g. [year] or your own shortcodes.',
        'canvas_placeholder' => 'Type a shortcode such as [year] in the sidebar.',
        'canvas_unknown' => 'No registered shortcode found in :code.',
        'code' => 'Shortcode',
        'code_hint' => 'Text around the shortcode is shown as written.',
        'reference_title' => 'Available shortcodes',
        'reference_empty' => 'This site has no shortcodes yet. A developer can add them with <code>FilamentPageBuilderPlugin::shortcode()</code>.',

        /** Descriptions for the shortcodes the package registers itself, listed beside their examples. */
        'builtin_year' => 'The current year.',
        'builtin_date' => "Today's date. Optional format, as PHP writes dates.",
    ],

    /**
     * What to paste instead, for an address `EmbedUrl` refuses to frame. A bare refusal
     * ("unsupported link") is the failure mode this replaced, so these are full sentences.
     */
    'problems' => [
        'short_map' => 'Short map links cannot be embedded. In Google Maps choose Share → Embed a map, copy the HTML and paste it here.',
        'directions' => 'Directions cannot be embedded. Search for the place instead and paste that link, or use Share → Embed a map.',
        'short_form' => 'Short form links cannot be embedded. Open the form and paste the full docs.google.com address.',
        'unknown' => 'Paste a link from YouTube, Vimeo, Google Maps, Docs, Forms, Slides or Drive, Facebook, Spotify or Canva — or the whole “Embed” code a site gives you.',
    ],
];
