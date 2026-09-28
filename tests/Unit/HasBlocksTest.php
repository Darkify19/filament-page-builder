<?php

use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Page;

it('mints an id when ensureBlockIds is given an explicit null', function (): void {
    $blocks = Page::ensureBlockIds([
        ['id' => null, 'type' => 'heading', 'data' => []],
        ['id' => '', 'type' => 'heading', 'data' => []],
        ['type' => 'heading', 'data' => []],
        ['id' => 'kept', 'type' => 'heading', 'data' => []],
    ]);

    expect($blocks)->toHaveCount(4)
        ->and($blocks[0]['id'])->toBeString()->not->toBe('')
        ->and($blocks[1]['id'])->toBeString()->not->toBe('')
        ->and($blocks[2]['id'])->toBeString()->not->toBe('')
        ->and($blocks[3]['id'])->toBe('kept');
});
