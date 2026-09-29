<?php

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;

it('turns the links editors paste into addresses a provider lets us frame', function (string $pasted, string $src): void {
    $embed = EmbedUrl::resolve($pasted);

    expect($embed)->not->toBeNull()
        ->and(EmbedUrl::src($embed))->toBe($src);
})->with([
    'watch page' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?playsinline=1&rel=0'],
    'share link' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?playsinline=1&rel=0'],
    'short' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?playsinline=1&rel=0'],
    'no scheme' => ['youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?playsinline=1&rel=0'],
    'timestamped' => ['https://youtu.be/dQw4w9WgXcQ?t=1m5s', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=65&playsinline=1&rel=0'],
    'vimeo' => ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871?dnt=1'],
    'unlisted vimeo' => ['https://vimeo.com/76979871/abc123', 'https://player.vimeo.com/video/76979871?h=abc123&dnt=1'],
    'maps search' => ['https://www.google.com/maps?q=UPLB', 'https://www.google.com/maps?q=UPLB&output=embed'],
    'maps place' => ['https://www.google.com/maps/place/UP+Los+Ba%C3%B1os/@14.16,121.24,15z', 'https://www.google.com/maps?q=UP+Los+Ba%C3%B1os&z=15&output=embed'],
    'maps embed' => ['https://www.google.com/maps/embed?pb=!1m18', 'https://www.google.com/maps/embed?pb=!1m18'],
    'google form' => ['https://docs.google.com/forms/d/e/1FAIpQL/viewform?usp=sf_link', 'https://docs.google.com/forms/d/e/1FAIpQL/viewform?embedded=true'],
    'google slides' => ['https://docs.google.com/presentation/d/abc_123/edit#slide=id.p', 'https://docs.google.com/presentation/d/abc_123/embed'],
    'drive file' => ['https://drive.google.com/file/d/xyz/view?usp=sharing', 'https://drive.google.com/file/d/xyz/preview'],
    'spotify' => ['https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC?si=1', 'https://open.spotify.com/embed/track/4uLU6hMCjMI75M1A2tKUQC'],
]);

it('reads the address out of a pasted embed snippet', function (): void {
    $embed = EmbedUrl::resolve('<iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ?si=abc&amp;start=3" title="YouTube video player" frameborder="0" allowfullscreen></iframe>');

    expect($embed['provider'])->toBe('youtube')
        ->and($embed['id'])->toBe('dQw4w9WgXcQ')
        ->and($embed['start'])->toBe(3);
});

it('applies start, stop, autoplay and looping to a video', function (): void {
    $src = EmbedUrl::src(EmbedUrl::resolve('https://youtu.be/dQw4w9WgXcQ'), [
        'start' => 90,
        'end' => 120,
        'autoplay' => true,
        'loop' => true,
        'controls' => false,
    ]);

    expect($src)->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=90&end=120&autoplay=1&mute=1&loop=1&playlist=dQw4w9WgXcQ&controls=0&playsinline=1&rel=0')
        ->and(EmbedUrl::src(EmbedUrl::resolve('https://vimeo.com/1'), ['start' => 42]))->toEndWith('#t=42s');
});

it('reads the times editors type', function (mixed $typed, ?int $seconds): void {
    expect(EmbedUrl::seconds($typed))->toBe($seconds);
})->with([
    ['90', 90],
    ['1:30', 90],
    ['01:02:03', 3723],
    ['1m30s', 90],
    ['2h', 7200],
    [45, 45],
    ['', null],
    ['soon', null],
]);

it('explains what to paste instead of a link it cannot frame', function (): void {
    expect(EmbedUrl::resolve('https://maps.app.goo.gl/abc'))->toBeNull()
        ->and(EmbedUrl::problem('https://maps.app.goo.gl/abc'))->toContain('Embed a map')
        ->and(EmbedUrl::problem('https://evil.example/x'))->toContain('Paste a link')
        ->and(EmbedUrl::problem('https://youtu.be/dQw4w9WgXcQ'))->toBeNull();
});

it('frames extra hosts only when the application allows them', function (): void {
    expect(EmbedBlock::allows('https://embed.example.org/widget'))->toBeFalse();

    app(BlockRegistry::class)->allowEmbedHosts(['example.org']);

    expect(EmbedBlock::allows('https://embed.example.org/widget'))->toBeTrue()
        ->and(EmbedBlock::allows('http://embed.example.org/widget'))->toBeFalse();
});
