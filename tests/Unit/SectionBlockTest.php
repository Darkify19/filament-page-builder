<?php

use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;

it('exposes one slot per column', function (): void {
    expect(SectionBlock::slots(['columns' => 3]))->toBe(['col-0', 'col-1', 'col-2']);
});

it('keeps a ratio that matches the column count', function (): void {
    expect(SectionBlock::ratioFor(3, '1-2-1'))->toBe('1-2-1');
});

it('falls back when the stored ratio is for a different column count', function (): void {
    expect(SectionBlock::ratioFor(3, '1-1'))->toBe('1-1-1')
        ->and(SectionBlock::ratioFor(1, '1-1'))->toBe('1')
        ->and(SectionBlock::ratioFor(4, '1-2'))->toBe('1-1-1-1');
});

it('offers only ratios that produce one track per column', function (): void {
    expect(array_keys(SectionBlock::ratiosFor(2)))->toBe(['1-1', '1-2', '2-1'])
        ->and(array_keys(SectionBlock::ratiosFor(3)))->toBe(['1-1-1', '1-2-1']);
});
