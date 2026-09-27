<?php

use CarlJanzell\FilamentPageBuilder\Support\SafeUrl;

it('allows http, https, mailto, tel, hashes and same-site paths', function (string $url): void {
    expect(SafeUrl::allows($url))->toBeTrue()
        ->and(SafeUrl::href($url))->toBe($url);
})->with([
    'https://example.com/about',
    'http://example.com',
    'mailto:hello@example.com',
    'tel:+15551212',
    '#intro',
    '/about',
    '/pages/hello-world',
]);

it('refuses javascript, data and protocol-relative URLs', function (?string $url): void {
    expect(SafeUrl::allows($url))->toBeFalse()
        ->and(SafeUrl::href($url))->toBe('#');
})->with([
    'javascript:alert(1)',
    'JAVASCRIPT:alert(1)',
    'data:text/html,<h1>x</h1>',
    '//evil.example/payload',
    '',
    null,
    '   ',
]);
