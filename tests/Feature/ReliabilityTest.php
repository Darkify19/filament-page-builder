<?php

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\ImageBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\TextBlock;
use CarlJanzell\FilamentPageBuilder\PageBuilder;
use CarlJanzell\FilamentPageBuilder\Support\BlockTree;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks\NoteBlock;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks\RestrictedBlock;

beforeEach(function (): void {
    RestrictedBlock::$visible = false;

    app(BlockRegistry::class)->register([
        SectionBlock::class,
        TextBlock::class,
        ImageBlock::class,
        EmbedBlock::class,
        NoteBlock::class,
    ]);
});

it('remints a colliding id on mount and marks the page dirty', function (): void {
    $canvas = canvas(page([
        block('dup', data: ['text' => 'One']),
        block('dup', data: ['text' => 'Two']),
    ]));

    expect($canvas->get('blocks'))->toHaveCount(2)
        ->and($canvas->get('blocks.0.id'))->toBe('dup')
        ->and($canvas->get('blocks.1.id'))->not->toBe('dup')
        ->and($canvas->get('blocks.1.data.text'))->toBe('Two');

    $canvas->assertSet('isDirty', true);
});

it('refuses to save when the record was written elsewhere', function (): void {
    $record = page([block('a'), block('b')]);
    $canvas = canvas($record);

    $record->forceFill(['updated_at' => now()->addMinute()])->save();

    $canvas->call('moveBlock', 'a', 2)->call('save');

    expect(array_column($record->fresh()->blocks, 'id'))->toBe(['a', 'b']);
    $canvas->assertSet('isDirty', true);
});

it('writes after the lock matches the loaded timestamp', function (): void {
    $record = page([block('a'), block('b')]);

    canvas($record)->call('moveBlock', 'a', 2)->call('save')->assertSet('isDirty', false);

    expect(array_column($record->fresh()->blocks, 'id'))->toBe(['b', 'a']);
});

it('refuses to duplicate a section that holds a restricted descendant', function (): void {
    $canvas = canvas(page([
        ['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']],
        ['id' => 'secret', 'type' => 'restricted', 'parent' => 's', 'slot' => 'col-0', 'data' => ['html' => 'kept']],
    ]))->call('duplicateBlock', 's');

    expect($canvas->get('blocks'))->toHaveCount(2)
        ->and(ids($canvas))->toBe(['s', 'secret']);
    $canvas->assertSet('isDirty', false);
});

it('still duplicates a section whose children are all authorable', function (): void {
    $canvas = canvas(page([
        ['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']],
        ['id' => 't', 'type' => 'text', 'parent' => 's', 'slot' => 'col-0', 'data' => ['body' => 'Hi']],
    ]))->call('duplicateBlock', 's');

    expect($canvas->get('blocks'))->toHaveCount(4);
    $canvas->assertSet('isDirty', true);
});

it('refuses to nest a block under a leaf or a slot the parent does not expose', function (): void {
    $canvas = canvas(page([
        block('a'),
        ['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']],
    ]));

    $canvas->call('insertBlock', 'text', 0, 'a', 'col-0');
    expect(ids($canvas))->toBe(['a', 's']);

    $canvas->call('insertBlock', 'text', 0, 's', 'col-2');
    expect($canvas->get('blocks'))->toHaveCount(2);

    $canvas->call('moveBlock', 'a', 0, 's', 'col-9');
    expect($canvas->get('blocks.0.parent'))->toBeNull();

    $canvas->assertSet('isDirty', false);
});

it('refuses an inline write to a rich-text field', function (): void {
    $canvas = canvas(page([
        ['id' => 'n', 'type' => 'note', 'data' => ['body' => '<p>kept</p>']],
    ]))->call('setBlockField', 'n', 'body', '<script>alert(1)</script>');

    expect($canvas->get('blocks.0.data.body'))->toBe('<p>kept</p>');
    $canvas->assertSet('isDirty', false);
});

it('lists hidden-slot children and can move them back into view', function (): void {
    $canvas = canvas(page([
        ['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']],
        ['id' => 'hidden', 'type' => 'text', 'parent' => 's', 'slot' => 'col-2', 'data' => ['body' => 'Lost']],
    ]));

    expect($canvas->instance()->ghosts)->toHaveCount(1)
        ->and($canvas->instance()->ghosts[0]['reason'])->toBe('hidden-slot');

    $canvas->assertSee('Hidden (1)')
        ->call('revealGhost', 'hidden');

    $revealed = BlockTree::find($canvas->get('blocks'), 'hidden');

    expect($revealed['parent'])->toBe('s')
        ->and($revealed['slot'])->toBe('col-1');
    $canvas->assertSet('isDirty', true);
});

it('moves an orphan onto the page root', function (): void {
    $canvas = canvas(page([
        block('a'),
        ['id' => 'lost', 'type' => 'heading', 'parent' => 'gone', 'slot' => 'col-0', 'data' => []],
    ]))->call('revealGhost', 'lost');

    $revealed = BlockTree::find($canvas->get('blocks'), 'lost');

    expect($revealed['parent'])->toBeNull()
        ->and($revealed['slot'])->toBeNull();
});

it('stores a valid anchor and ignores an invalid one', function (): void {
    $canvas = canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockAnchor', 'intro');

    expect($canvas->get('blocks.0.anchor'))->toBe('intro');

    $canvas->set('blockAnchor', '1bad');

    expect($canvas->get('blocks.0.anchor'))->toBe('intro');
});

it('prints a stored image path as a public URL', function (): void {
    PageBuilder::idle();

    $html = view('page-builder::components.image', [
        'data' => ['src' => 'pages/foo.jpg', 'alt' => 'A photo'],
    ])->render();

    expect($html)->toContain('/storage/pages/foo.jpg')
        ->and($html)->not->toContain('src="pages/foo.jpg"');
});

it('prints a button href only when the URL is allowed', function (): void {
    PageBuilder::idle();

    $safe = view('page-builder::components.button', [
        'data' => ['label' => 'Go', 'url' => 'https://example.com'],
    ])->render();

    $unsafe = view('page-builder::components.button', [
        'data' => ['label' => 'Go', 'url' => 'javascript:alert(1)'],
    ])->render();

    expect($safe)->toContain('href="https://example.com"')
        ->and($unsafe)->toContain('href="#"')
        ->and($unsafe)->not->toContain('javascript:');
});

it('renders an allowlisted embed and skips anything else', function (): void {
    PageBuilder::idle();

    $ok = view('page-builder::components.embed', [
        'data' => ['url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    ])->render();

    $no = view('page-builder::components.embed', [
        'data' => ['url' => 'https://evil.example/embed'],
    ])->render();

    expect($ok)->toContain('<iframe')
        ->and($ok)->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and($no)->not->toContain('<iframe')
        ->and($no)->toContain('Paste a YouTube');
});

it('emits a valid anchor as an id on the public wrapper', function (): void {
    PageBuilder::idle();

    $html = view('page-builder::components.blocks', [
        'blocks' => [[
            'id' => 'a',
            'type' => 'text',
            'data' => ['body' => 'Hello'],
            'anchor' => 'intro',
        ]],
    ])->render();

    expect($html)->toContain('id="intro"')
        ->and($html)->toContain('Hello');
});

it('pops the render context when a block view throws', function (): void {
    PageBuilder::editing('a', 'heading');

    try {
        PageBuilder::renderSafely(function (): string {
            throw new RuntimeException('boom');
        });
        expect(false)->toBeTrue();
    } catch (RuntimeException) {
        expect(PageBuilder::isEditing())->toBeFalse()
            ->and(PageBuilder::currentBlockId())->toBeNull();
    }
});

it('shows empty-state copy and search chrome on a blank canvas', function (): void {
    canvas(page())
        ->assertSee('Start a layout')
        ->assertSee('Search blocks')
        ->assertSee('Saved')
        ->assertSee('role="tablist"', escape: false);
});
