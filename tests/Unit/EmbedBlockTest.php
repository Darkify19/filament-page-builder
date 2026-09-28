<?php

use CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock;

it('allows YouTube, Vimeo and Google Maps hosts', function (string $url): void {
    expect(EmbedBlock::allows($url))->toBeTrue();
})->with([
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'https://youtube.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ',
    'https://player.vimeo.com/video/123',
    'https://vimeo.com/123',
    'https://www.google.com/maps/embed?pb=1',
    'https://maps.google.com/maps?q=1',
]);

it('refuses hosts that are not on the allowlist', function (?string $url): void {
    expect(EmbedBlock::allows($url))->toBeFalse();
})->with([
    'https://evil.example/embed',
    'javascript:alert(1)',
    'https://example.com',
    '/relative',
    '',
    null,
]);
