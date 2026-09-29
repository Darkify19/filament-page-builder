<?php

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\SpacerBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\TextBlock;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks\RestrictedBlock;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Filament\PageResource;

beforeEach(function (): void {
    PageResource::$canEdit = true;
    RestrictedBlock::$visible = false;

    app(BlockRegistry::class)->register([SectionBlock::class, TextBlock::class, SpacerBlock::class]);
});

/* ── The Style and Layout tabs ─────────────────────────── */

it('stores spacing typed into the Layout tab', function (): void {
    $canvas = canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockLayout.padding_top', '24')
        ->set('blockLayout.margin_left', '-8');

    expect($canvas->get('blocks.0.style'))->toBe(['padding_top' => 24, 'margin_left' => -8])
        ->and($canvas->get('isDirty'))->toBeTrue();
});

it('stores colours and alignment from the Style tab', function (): void {
    $canvas = canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockStyle.text_align', 'right')
        ->set('blockStyle.text_color', '#8a1538');

    expect($canvas->get('blocks.0.style'))->toBe(['text_align' => 'right', 'text_color' => '#8a1538']);
});

it('drops a crafted value instead of storing it', function (): void {
    $canvas = canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockStyle.text_color', 'red;}</style><script>alert(1)</script>');

    expect($canvas->get('blocks.0.style'))->toBeNull()
        ->and($canvas->get('isDirty'))->toBeFalse();
});

it('keeps both tabs when either one changes', function (): void {
    $canvas = canvas(page([['id' => 'a', 'type' => 'heading', 'data' => [], 'style' => ['padding_top' => 10, 'text_color' => '#fff']]]))
        ->call('selectBlock', 'a')
        ->set('blockStyle.text_align', 'center');

    expect($canvas->get('blocks.0.style'))->toBe(['padding_top' => 10, 'text_align' => 'center', 'text_color' => '#fff']);
});

it('fills the tabs from the block that is selected', function (): void {
    $canvas = canvas(page([
        ['id' => 'a', 'type' => 'heading', 'data' => [], 'style' => ['padding_top' => 10, 'text_color' => '#fff']],
        block('b'),
    ]))->call('selectBlock', 'a');

    expect(filled_state($canvas->get('blockStyle')))->toBe(['text_color' => '#fff'])
        ->and(filled_state($canvas->get('blockLayout')))->toEqual(['padding_top' => 10]);

    $canvas->call('selectBlock', 'b');

    expect(filled_state($canvas->get('blockStyle')))->toBe([])
        ->and(filled_state($canvas->get('blockLayout')))->toBe([]);
});

it('undoes a style change in one step', function (): void {
    $canvas = canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockLayout.padding_top', '24')
        ->call('undo');

    expect($canvas->get('blocks.0.style'))->toBeNull()
        ->and(filled_state($canvas->get('blockLayout')))->toBe([])
        ->and($canvas->get('isDirty'))->toBeFalse();
});

it('refuses to restyle a block the user may not author', function (): void {
    canvas(page([block('r', 'restricted', ['html' => '<b>x</b>'])]))
        ->call('setBlockStyle', 'r', 'text_align', 'center')
        ->assertSet('blocks.0.style', null);
});

it('offers no custom styles when the panel turns them off', function (): void {
    FilamentPageBuilderPlugin::get()->customStyles(false);

    canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->set('blockStyle.text_align', 'right')
        ->assertSet('blocks.0.style', null);
});

it('saves the style with the page', function (): void {
    $page = page([block('a')]);

    canvas($page)
        ->call('selectBlock', 'a')
        ->set('blockLayout.width', '60')
        ->call('save');

    expect($page->fresh()->blocks[0]['style'])->toBe(['width' => 60]);
});

/* ── The alignment buttons and the right-click menu ────── */

it('aligns a block from its bar and toggles the alignment off again', function (): void {
    $canvas = canvas(page([block('a')]))->call('setBlockStyle', 'a', 'text_align', 'justify');

    expect($canvas->get('blocks.0.style'))->toBe(['text_align' => 'justify']);

    $canvas->call('setBlockStyle', 'a', 'text_align', '');

    expect($canvas->get('blocks.0.style'))->toBeNull();
});

it('ignores a style key it does not know', function (): void {
    canvas(page([block('a')]))
        ->call('setBlockStyle', 'a', 'position', 'fixed')
        ->assertSet('blocks.0.style', null)
        ->assertSet('isDirty', false);
});

it('copies one block\'s look onto another', function (): void {
    $canvas = canvas(page([
        ['id' => 'a', 'type' => 'heading', 'data' => [], 'style' => ['text_color' => '#123456'], 'settings' => ['padding' => 'lg']],
        block('b'),
    ]));

    $look = $canvas->instance()->copyBlockStyle('a');

    $canvas->call('pasteBlockStyle', 'b', $look);

    expect($canvas->get('blocks.1.style'))->toBe(['text_color' => '#123456'])
        ->and($canvas->get('blocks.1.settings'))->toBe(['padding' => 'lg']);
});

it('cleans a pasted look as if it had been typed', function (): void {
    $canvas = canvas(page([block('a')]))->call('pasteBlockStyle', 'a', [
        'style' => ['text_color' => 'url(javascript:1)', 'padding_top' => 5],
        'settings' => ['padding' => '13px lime'],
    ]);

    expect($canvas->get('blocks.0.style'))->toBe(['padding_top' => 5])
        ->and($canvas->get('blocks.0.settings'))->toBe([]);
});

it('resets a block\'s style and presets', function (): void {
    canvas(page([['id' => 'a', 'type' => 'heading', 'data' => ['text' => 'Kept'], 'style' => ['padding_top' => 5], 'settings' => ['padding' => 'lg']]]))
        ->call('resetBlockStyle', 'a')
        ->assertSet('blocks.0.style', null)
        ->assertSet('blocks.0.settings', [])
        ->assertSet('blocks.0.data.text', 'Kept');
});

/* ── Resizing ──────────────────────────────────────────── */

it('sets a width from a drag of the right edge', function (): void {
    $canvas = canvas(page([block('a')]))->call('resizeBlock', 'a', 'width', 62.4);

    expect($canvas->get('blocks.0.style'))->toBe(['width' => 62.4, 'width_unit' => '%']);

    $canvas->call('resizeBlock', 'a', 'width', 100);

    expect($canvas->get('blocks.0.style'))->toBeNull();
});

it('sets a minimum height from a drag of the bottom edge', function (): void {
    canvas(page([block('a')]))
        ->call('resizeBlock', 'a', 'height', 240)
        ->assertSet('blocks.0.style', ['min_height' => 240, 'min_height_unit' => 'px']);
});

it('sizes a spacer itself rather than its box', function (): void {
    $canvas = canvas(page([['id' => 's', 'type' => 'spacer', 'data' => ['height' => 'md']]]))
        ->call('resizeBlock', 's', 'height', 88);

    expect($canvas->get('blocks.0.data.size'))->toBe(88)
        ->and($canvas->get('blocks.0.style'))->toBeNull();

    $canvas->call('resizeBlock', 's', 'height', null);

    expect($canvas->get('blocks.0.data'))->toBe(['height' => 'md']);
});

it('stores dragged column widths as a custom ratio', function (): void {
    $canvas = canvas(page([['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']]]))
        ->call('resizeColumns', 's', [31.6, 68.4]);

    expect($canvas->get('blocks.0.data.ratio'))->toBe('32-68')
        ->and(SectionBlock::customTracks(2, '32-68'))->toBe('minmax(0, 32fr) minmax(0, 68fr)');
});

it('snaps dragged column widths onto a preset they match', function (): void {
    canvas(page([['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']]]))
        ->call('resizeColumns', 's', [33, 67])
        ->assertSet('blocks.0.data.ratio', '1-2');
});

it('refuses column widths that do not fit the section', function (): void {
    canvas(page([['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']]]))
        ->call('resizeColumns', 's', [2, 98])
        ->call('resizeColumns', 's', [30, 30, 40])
        ->assertSet('blocks.0.data.ratio', '1-1')
        ->assertSet('isDirty', false);
});

/* ── Selecting an old block changes nothing ────────────── */

it('does not dirty the page when an older block meets new fields', function (): void {
    canvas(page([['id' => 's', 'type' => 'section', 'data' => ['columns' => 2, 'ratio' => '1-1']], block('b')]))
        ->call('selectBlock', 's')
        ->call('selectBlock', 'b')
        ->assertSet('isDirty', false)
        ->assertSet('canUndo', false);
});

/* ── The inspector knows which element it is ───────────── */

it('names the selected element and the block that holds it', function (): void {
    $canvas = canvas(page([
        ['id' => 's', 'type' => 'section', 'data' => ['columns' => 2]],
        ['id' => 't', 'type' => 'text', 'parent' => 's', 'slot' => 'col-0', 'data' => []],
    ]))->call('selectBlock', 't');

    expect($canvas->instance()->selectedBlock)->toMatchArray([
        'id' => 't',
        'label' => 'Text',
        'parentId' => 's',
        'parentLabel' => 'Section',
    ]);

    $canvas->assertSee('Inside Section');
});
