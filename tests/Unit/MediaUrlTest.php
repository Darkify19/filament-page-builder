<?php

use CarlJanzell\FilamentPageBuilder\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;

it('leaves absolute and temporary URLs alone', function (string $url): void {
    expect(MediaUrl::public($url))->toBe($url);
})->with([
    'https://cdn.example/photo.jpg',
    'http://localhost/tmp/livewire.jpg',
    '//cdn.example/photo.jpg',
    'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==',
    'blob:https://example.com/1',
]);

it('resolves a stored public-disk path to a URL', function (): void {
    Storage::fake('public');

    expect(MediaUrl::public('pages/foo.jpg'))->toBe(Storage::disk('public')->url('pages/foo.jpg'));
});

it('returns null for an empty path', function (): void {
    expect(MediaUrl::public(null))->toBeNull()
        ->and(MediaUrl::public(''))->toBeNull();
});
