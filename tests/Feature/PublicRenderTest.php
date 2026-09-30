<?php

use CarlJanzell\FilamentPageBuilder\BlockRegistries;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    // Panels register their plugins lazily, so both have to be resolved first.
    Filament::getPanel('testing');
    Filament::getPanel('second');
});

function publicPage(array $blocks, string $attributes = ''): string
{
    return Blade::render(
        '<x-page-builder::blocks :blocks="$blocks" '.$attributes.' />',
        ['blocks' => $blocks],
    );
}

it('renders with the default panel\'s blocks when none is named', function (): void {
    $html = publicPage([block('a', 'heading', ['text' => 'Hello']), block('b', 'banner', ['caption' => 'Hi'])]);

    expect($html)->toContain('blk-heading')->toContain('blk-banner');
});

it('renders with the blocks of the panel it names', function (): void {
    // The second panel registers only headings, so its render skips the banner.
    $html = publicPage([block('a', 'heading', ['text' => 'Hello']), block('b', 'banner', ['caption' => 'Hi'])], 'panel="second"');

    expect($html)->toContain('blk-heading')->not->toContain('blk-banner');
});

it('goes back to the panel being served once the render is done', function (): void {
    publicPage([block('a', 'heading')], 'panel="second"');

    expect(array_keys(app(BlockRegistries::class)->current()->all()))
        ->toBe(['heading', 'banner', 'restricted']);
});
