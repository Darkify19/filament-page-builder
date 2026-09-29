<?php

use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;

it('keeps values of the right kind and drops everything else', function (): void {
    expect(BlockStyle::sanitize([
        'padding_top' => '24',
        'padding_unit' => 'rem',
        'margin_left' => -12.5,
        'text_align' => 'justify',
        'text_color' => '#FFAA00',
        'font_weight' => 700,
        'shadow' => 'md',
        'css_class' => 'hero  fade-in',
        'unknown' => 'kept?',
        'text_transform' => 'shout',
    ]))->toBe([
        'padding_top' => 24,
        'margin_left' => -12.5,
        'padding_unit' => 'rem',
        'text_align' => 'justify',
        'text_color' => '#ffaa00',
        'font_weight' => '700',
        'shadow' => 'md',
        'css_class' => 'hero fade-in',
    ]);
});

it('clamps numbers into their range', function (): void {
    expect(BlockStyle::sanitize(['padding_top' => -40, 'overlay_opacity' => 250, 'width' => 0]))
        ->toBe(['padding_top' => 0, 'overlay_opacity' => 100, 'width' => 1]);
});

it('accepts the colour forms a picker writes and nothing else', function (mixed $colour, ?string $expected): void {
    expect(BlockStyle::color($colour))->toBe($expected);
})->with([
    ['#fff', '#fff'],
    ['#11223380', '#11223380'],
    ['rgba(0, 0, 0, 0.5)', 'rgba(0, 0, 0, 0.5)'],
    ['hsl(210, 50%, 40%)', 'hsl(210, 50%, 40%)'],
    ['transparent', 'transparent'],
    ['red', null],
    ['#fff;background:url(javascript:alert(1))', null],
    ['rgb(0,0,0));}</style><script>', null],
    ['expression(alert(1))', null],
    [['#fff'], null],
]);

it('refuses URLs and paths that could break out of the style attribute', function (): void {
    expect(BlockStyle::url('javascript:alert(1)'))->toBeNull()
        ->and(BlockStyle::url('https://ex.com/a.mp4") no-repeat;x:url("'))->toBeNull()
        ->and(BlockStyle::url('https://cdn.example.com/clip.mp4'))->toBe('https://cdn.example.com/clip.mp4')
        ->and(BlockStyle::path('../../.env'))->toBeNull()
        ->and(BlockStyle::path('pages/photo.jpg'))->toBe('pages/photo.jpg');
});

it('stores switches only when they are on', function (): void {
    expect(BlockStyle::sanitize(['background_fixed' => false, 'video_once' => true]))->toBe(['video_once' => true]);
});

it('puts margin and width outside and everything else inside', function (): void {
    $compiled = BlockStyle::compile([
        'margin_top' => 10,
        'width' => 50,
        'padding_left' => 8,
        'text_color' => '#123456',
        'element_align' => 'center',
    ]);

    expect($compiled['outer'])->toBe('margin-top:10px;width:50%;margin-left:auto;margin-right:auto')
        ->and($compiled['inner'])->toBe('padding-left:8px;color:#123456')
        ->and($compiled['combined'])->toBe($compiled['outer'].';'.$compiled['inner'])
        ->and($compiled['background'])->toBeNull();
});

it('draws a gradient background as plain CSS', function (): void {
    $compiled = BlockStyle::compile([
        'background_type' => 'gradient',
        'gradient_from' => '#8a1538',
        'gradient_to' => 'rgba(0, 87, 63, 0.9)',
        'gradient_angle' => 135,
    ]);

    expect($compiled['inner'])->toBe('background-image:linear-gradient(135deg, #8a1538, rgba(0, 87, 63, 0.9))');
});

it('describes a background video and its overlay as a layer', function (): void {
    $compiled = BlockStyle::compile([
        'background_type' => 'video',
        'background_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'video_start' => 30,
        'overlay_color' => '#000000',
        'overlay_opacity' => 40,
    ]);

    expect($compiled['background']['video']['kind'])->toBe('iframe')
        ->and($compiled['background']['video']['src'])->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and($compiled['background']['video']['src'])->toContain('start=30')
        ->and($compiled['background']['video']['src'])->toContain('loop=1')
        ->and($compiled['background']['overlay'])->toBe('background:#000000;opacity:0.4')
        ->and($compiled['inner'])->toContain('isolation:isolate');
});

it('plays an uploaded background video from a file', function (): void {
    $compiled = BlockStyle::compile([
        'background_type' => 'video',
        'background_video' => 'pages/loop.mp4',
        'video_start' => 5,
        'video_end' => 12,
        'video_once' => true,
    ]);

    expect($compiled['background']['video'])->toMatchArray([
        'kind' => 'file',
        'start' => 5,
        'end' => 12,
        'loop' => false,
    ])->and($compiled['background']['video']['src'])->toContain('/storage/pages/loop.mp4');
});

it('marks a block with a height so a spacer can fill it', function (): void {
    expect(BlockStyle::compile(['min_height' => 120])['sized'])->toBeTrue()
        ->and(BlockStyle::compile([])['sized'])->toBeFalse();
});

it('splits a style between the Style and Layout tabs', function (): void {
    [$look, $layout] = BlockStyle::split(['text_color' => '#fff', 'padding_top' => 4, 'css_class' => 'x']);

    expect($look)->toBe(['text_color' => '#fff'])
        ->and($layout)->toBe(['padding_top' => 4, 'css_class' => 'x']);
});
