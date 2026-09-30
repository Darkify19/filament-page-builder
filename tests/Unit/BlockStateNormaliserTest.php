<?php

use CarlJanzell\FilamentPageBuilder\Support\BlockStateNormaliser;

beforeEach(function (): void {
    $this->normaliser = app(BlockStateNormaliser::class);
});

it('leaves rich text html as it is, since Trix already holds the stored shape', function (): void {
    $html = '<p>Hello <strong>there</strong></p>';

    expect($this->normaliser->normaliseData(['body' => $html])['body'])->toBe($html);
});

it('walks into nested arrays so fields inside repeaters are handled', function (): void {
    $result = $this->normaliser->normaliseData(
        ['items' => [['body' => '<p>Nested</p>', 'image' => ['uuid' => 'pages/nested.jpg']]]],
        ['image'],
    );

    expect($result['items'][0])->toBe(['body' => '<p>Nested</p>', 'image' => 'pages/nested.jpg']);
});

it('reduces a declared file field to its stored path', function (): void {
    $result = $this->normaliser->normaliseData(
        ['image' => ['some-uuid' => 'pages/hero.jpg']],
        ['image'],
    );

    expect($result['image'])->toBe('pages/hero.jpg');
});

it('leaves an undeclared array field alone', function (): void {
    $result = $this->normaliser->normaliseData(['image' => ['pages/hero.jpg']]);

    expect($result['image'])->toBe(['pages/hero.jpg']);
});

it('resolves an empty file field to null', function (): void {
    expect($this->normaliser->resolveFileState([]))->toBeNull()
        ->and($this->normaliser->resolveFileState(null))->toBeNull()
        ->and($this->normaliser->resolveFileState(''))->toBe('');
});

it('drops entries that are not blocks', function (): void {
    $blocks = $this->normaliser->normalise([
        ['id' => 'a', 'type' => 'heading', 'data' => ['text' => 'Hi']],
        ['id' => 'b'],
        'not an array',
    ]);

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['type'])->toBe('heading');
});

it('returns nothing for a non array', function (): void {
    expect($this->normaliser->normalise('nope'))->toBe([])
        ->and($this->normaliser->normaliseData('nope'))->toBe([]);
});
