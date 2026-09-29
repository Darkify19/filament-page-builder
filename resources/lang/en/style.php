<?php

/**
 * The inspector's Style and Layout tabs.
 *
 * Purely a labels file: nothing here decides what a field is called or what it accepts —
 * `BlockStyle` owns that. Keeping them apart means a field can be renamed in eight
 * languages without a schema rebuild, and a schema can change without touching a sentence.
 *
 * Technical values are deliberately not translated. `px`, `rem`, `1 / 1`, `16:9` and
 * `0.5×` are the same token everywhere, and a translator "helpfully" localising a CSS unit
 * would emit markup that silently stops applying.
 */
return [
    'tabs' => [
        'typography' => 'Text',
        'background' => 'Background',
        'border' => 'Border and shadow',
        'spacing' => 'Spacing',
        'size' => 'Size and position',
        'developer' => 'For developers',
    ],

    'common' => [
        'auto' => 'Auto',
        'none' => 'None',
        'unit' => 'Unit',
        'colour' => 'Colour',
        'size' => 'Size',
        'width' => 'Width',
        'max_width' => 'Max width',
        'min_height' => 'Min height',
        'line_height' => 'Line height',
        'letter_spacing' => 'Letter spacing',
        'left' => 'Left',
        'centre' => 'Centre',
        'right' => 'Right',
        'justify' => 'Justify',
        'top' => 'Top',
        'bottom' => 'Bottom',
    ],

    'alignment' => [
        'label' => 'Alignment',
        'align_left' => 'Align left',
        'align_right' => 'Align right',
    ],

    'weight' => [
        'label' => 'Weight',
        'light' => 'Light',
        'regular' => 'Regular',
        'medium' => 'Medium',
        'semibold' => 'Semibold',
        'bold' => 'Bold',
        'extra_bold' => 'Extra bold',
        'black' => 'Black',
    ],

    'case' => [
        'label' => 'Case',
        'capitalise' => 'Capitalise',
        'as_typed' => 'As typed',
    ],

    'background' => [
        'type_none' => 'None',
        'type_colour' => 'Colour',
        'type_gradient' => 'Gradient',
        'type_image' => 'Image',
        'type_video' => 'Video',
        'fallback_colour' => 'Fallback colour',
        'from' => 'From',
        'to' => 'To',
        'type' => 'Type',
        'linear' => 'Linear',
        'radial' => 'Radial',
        'direction' => 'Direction',
        'image' => 'Image',
        'fit' => 'Fit',
        'fit_fill' => 'Fill',
        'fit_contain' => 'Fit inside',
        'fit_auto' => 'Actual size',
        'focus' => 'Focus',
        'focus_centre' => 'Centre',
        'focus_top' => 'Top',
        'focus_bottom' => 'Bottom',
        'focus_left' => 'Left',
        'focus_right' => 'Right',
        'focus_top_left' => 'Top left',
        'focus_top_right' => 'Top right',
        'focus_bottom_left' => 'Bottom left',
        'focus_bottom_right' => 'Bottom right',
        'parallax' => 'Parallax',
        'tile' => 'Tile',
        'video_file' => 'Video file',
        'video_link' => '…or a video link',
        'video_link_placeholder' => 'YouTube, Vimeo or an .mp4 link',
        'video_link_invalid' => 'Enter an http(s) link.',
        'start_at' => 'Start at',
        'stop_at' => 'Stop at',
        'play_once' => 'Play once',
        'play_once_hint' => 'Loops unless this is on',
        'speed' => 'Speed',
        'speed_normal' => 'Normal',
        'overlay' => 'Overlay',
        'strength' => 'Strength',
        'time_hint' => 'Seconds or m:ss',
    ],

    'border' => [
        'border' => 'Border',
        'line' => 'Line',
        'solid' => 'Solid',
        'dashed' => 'Dashed',
        'dotted' => 'Dotted',
        'double' => 'Double',
        'border_colour' => 'Border colour',
        'rounded_corners' => 'Rounded corners',
        'shadow' => 'Shadow',
        'shadow_subtle' => 'Subtle',
        'shadow_medium' => 'Medium',
        'shadow_large' => 'Large',
        'shadow_dramatic' => 'Dramatic',
        'opacity' => 'Opacity',
    ],

    'spacing' => [
        'padding' => 'Padding — space inside',
        'margin' => 'Margin — space outside',
    ],

    'size' => [
        'width_hint' => 'Or drag the right edge on the page.',
        'min_height_hint' => 'Or drag the bottom edge.',
        'position' => 'Position when narrower than its column',
    ],

    'developer' => [
        'css_classes' => 'CSS classes',
        'css_classes_placeholder' => 'hero-title fade-in',
        'css_classes_hint' => 'Target this block from Custom code or your own stylesheet.',
    ],
];
