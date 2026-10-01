<?php

use CarlJanzell\FilamentPageBuilder\Support\BlockTree;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Blocks\RestrictedBlock;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Filament\PageResource;
use Filament\Support\Enums\MaxWidth;

beforeEach(function (): void {
    PageResource::$canEdit = true;
    RestrictedBlock::$visible = false;
});

/* ── Loading ───────────────────────────────────────────── */

it('loads a record onto the canvas', function (): void {
    $canvas = canvas(page([block('a'), block('b')]));

    expect(ids($canvas))->toBe(['a', 'b']);
    $canvas->assertSet('isDirty', false)->assertSet('selectedId', null);
});

it('gives an id to a block that has none', function (): void {
    $canvas = canvas(page([['type' => 'heading', 'data' => []]]));

    expect($canvas->get('blocks.0.id'))->not->toBeNull();
});

it('refuses the canvas to someone who may not edit the record', function (): void {
    PageResource::$canEdit = false;

    canvas(page())->assertForbidden();
});

it('opens as a full-screen editor without Filament page chrome', function (): void {
    $page = page();
    $canvas = canvas($page);
    $instance = $canvas->instance();

    expect($instance->getHeading())->toBe('')
        ->and($instance->getBreadcrumbs())->toBe([])
        ->and($instance->getMaxContentWidth())->toBe(MaxWidth::Full)
        ->and($instance->getExtraBodyAttributes()['class'])->toContain('fpb-edit-mode')
        ->and($instance->getPageClasses())->toContain('fpb-editor-page')
        ->and($instance->getPageClasses())->not->toContain('fpb-page')
        ->and($instance->exitUrl())->toBe(PageResource::getUrl('index'))
        ->and($instance->exitLabel())->toBe('Back to '.PageResource::getBreadcrumb());

    $canvas
        ->assertSee('fpb-chrome', escape: false)
        ->assertSee('fpb-chrome-title', escape: false)
        ->assertSee('Back to')
        ->assertSee('Save layout')
        ->assertDontSeeHtml('Design: '.$page->title.'</h1>');
});

it('ships a one-panel workspace switcher for a narrow design canvas', function (): void {
    canvas(page())
        ->assertSee('fpb-dock', escape: false)
        ->assertSee('aria-label="Design workspace"', escape: false)
        ->assertSee('aria-label="Add a block"', escape: false)
        ->assertSee('fpb-add', escape: false)
        ->assertSee('data-workspace', escape: false)
        ->assertSee('tap Blocks to pick one');

    canvas(page([block('a')]))
        ->assertSeeHtml('aria-label="Edit Heading"')
        ->assertSeeHtml('aria-label="Move Heading up"')
        ->assertSeeHtml('aria-label="Move Heading down"');
});

it('offers only the blocks the user may author in the palette', function (): void {
    $types = array_column(canvas(page())->instance()->palette, 'type');

    expect($types)->toBe(['heading', 'banner']);

    RestrictedBlock::$visible = true;

    $types = array_column(canvas(page())->instance()->palette, 'type');

    expect($types)->toContain('restricted');
});

/* ── Reordering ────────────────────────────────────────── */

it('moves a block down the page', function (): void {
    $canvas = canvas(page([block('a'), block('b'), block('c')]))
        ->call('moveBlock', 'a', 2);

    expect(ids($canvas))->toBe(['b', 'a', 'c']);
    $canvas->assertSet('isDirty', true);
});

it('moves a block up the page', function (): void {
    $canvas = canvas(page([block('a'), block('b'), block('c')]))
        ->call('moveBlock', 'c', 0);

    expect(ids($canvas))->toBe(['c', 'a', 'b']);
});

it('clamps a move past the end of the page', function (): void {
    $canvas = canvas(page([block('a'), block('b')]))
        ->call('moveBlock', 'a', 99);

    expect(ids($canvas))->toBe(['b', 'a']);
});

it('ignores a move of a block that is not there', function (): void {
    canvas(page([block('a')]))
        ->call('moveBlock', 'ghost', 0)
        ->assertSet('isDirty', false);
});

/* ── Inserting ─────────────────────────────────────────── */

it('appends an inserted block and selects it', function (): void {
    $canvas = canvas(page([block('a')]))->call('insertBlock', 'banner');

    expect($canvas->get('blocks'))->toHaveCount(2)
        ->and($canvas->get('blocks.1.type'))->toBe('banner')
        ->and($canvas->get('selectedId'))->toBe($canvas->get('blocks.1.id'));
});

it('inserts at a given position', function (): void {
    $canvas = canvas(page([block('a'), block('b')]))->call('insertBlock', 'banner', 1);

    expect(array_column($canvas->get('blocks'), 'type'))
        ->toBe(['heading', 'banner', 'heading']);
});

it('refuses to insert a block the user may not author', function (): void {
    canvas(page())
        ->call('insertBlock', 'restricted')
        ->assertSet('blocks', [])
        ->assertSet('isDirty', false);
});

it('refuses to insert an unregistered type', function (): void {
    canvas(page())->call('insertBlock', 'nope')->assertSet('blocks', []);
});

/* ── Duplicating and removing ──────────────────────────── */

it('duplicates a block directly beneath itself with a new id', function (): void {
    $canvas = canvas(page([block('a', data: ['text' => 'Hi']), block('b')]))
        ->call('duplicateBlock', 'a');

    expect(ids($canvas))->toHaveCount(3)
        ->and($canvas->get('blocks.1.data.text'))->toBe('Hi')
        ->and($canvas->get('blocks.1.id'))->not->toBe('a')
        ->and($canvas->get('blocks.2.id'))->toBe('b');
});

it('refuses to duplicate a block the user may not author', function (): void {
    canvas(page([block('a', 'restricted')]))
        ->call('duplicateBlock', 'a')
        ->assertCount('blocks', 1);
});

it('removes a block and clears the selection when it was selected', function (): void {
    canvas(page([block('a'), block('b')]))
        ->call('selectBlock', 'a')
        ->call('removeBlock', 'a')
        ->assertCount('blocks', 1)
        ->assertSet('selectedId', null);
});

it('keeps the selection when a different block is removed', function (): void {
    canvas(page([block('a'), block('b')]))
        ->call('selectBlock', 'a')
        ->call('removeBlock', 'b')
        ->assertSet('selectedId', 'a');
});

/* ── The inspector ─────────────────────────────────────── */

it('fills the inspector from the selected block', function (): void {
    canvas(page([block('a', data: ['text' => 'Hello'])]))
        ->call('selectBlock', 'a')
        ->assertSet('blockData.text', 'Hello');
});

it('swaps inspector state when the selection changes', function (): void {
    canvas(page([block('a', data: ['text' => 'One']), block('b', data: ['text' => 'Two'])]))
        ->call('selectBlock', 'a')
        ->assertSet('blockData.text', 'One')
        ->call('selectBlock', 'b')
        ->assertSet('blockData.text', 'Two');
});

it('commits inspector edits back onto the block', function (): void {
    $canvas = canvas(page([block('a', data: ['text' => 'Before'])]))
        ->call('selectBlock', 'a')
        ->set('blockData.text', 'After');

    expect($canvas->get('blocks.0.data.text'))->toBe('After');
    $canvas->assertSet('isDirty', true);
});

it('withholds the fields of a block the user may not author', function (): void {
    $canvas = canvas(page([block('a', 'restricted', ['html' => '<b>kept</b>'])]))
        ->call('selectBlock', 'a');

    expect($canvas->instance()->isSelectedBlockEditable())->toBeFalse()
        ->and($canvas->get('blocks.0.data.html'))->toBe('<b>kept</b>');

    $canvas->assertSet('isDirty', false);
});

/* ── Saving ────────────────────────────────────────────── */

it('writes the canvas back to the record', function (): void {
    $page = page([block('a'), block('b')]);

    canvas($page)->call('moveBlock', 'a', 2)->call('save')->assertSet('isDirty', false);

    expect(array_column($page->fresh()->blocks, 'id'))->toBe(['b', 'a']);
});

it('commits a pending inspector edit before saving', function (): void {
    $page = page([block('a', data: ['text' => 'Before'])]);

    canvas($page)->call('selectBlock', 'a')->set('blockData.text', 'After')->call('save');

    expect($page->fresh()->blocks[0]['data']['text'])->toBe('After');
});

/* ── Content the canvas does not own ───────────────────── */

it('keeps a block whose type is no longer registered', function (): void {
    $page = page([block('a'), block('gone', 'retired', ['text' => 'Still here'])]);

    $canvas = canvas($page);

    expect(ids($canvas))->toBe(['a', 'gone']);

    $canvas->call('save');

    expect($page->fresh()->blocks[1])
        ->toMatchArray(['id' => 'gone', 'type' => 'retired', 'data' => ['text' => 'Still here']]);
});

it('renders a placeholder rather than the component for a retired type', function (): void {
    canvas(page([block('gone', 'retired')]))
        ->assertSee('no longer offers')
        ->assertDontSee('blk-heading', escape: false);
});

it('offers no fields for a retired type and says why', function (): void {
    $canvas = canvas(page([block('gone', 'retired', ['text' => 'Kept'])]))
        ->call('selectBlock', 'gone');

    expect($canvas->instance()->isSelectedBlockKnown())->toBeFalse()
        ->and($canvas->get('blocks.0.data.text'))->toBe('Kept');

    $canvas->assertSee('no longer registered')->assertSet('isDirty', false);
});

it('will not duplicate a retired block', function (): void {
    canvas(page([block('gone', 'retired')]))
        ->call('duplicateBlock', 'gone')
        ->assertCount('blocks', 1);
});

it('still lets a retired block be moved and removed', function (): void {
    $canvas = canvas(page([block('a'), block('gone', 'retired')]))
        ->call('moveBlock', 'gone', 0);

    expect(ids($canvas))->toBe(['gone', 'a']);

    $canvas->call('removeBlock', 'gone');

    expect(ids($canvas))->toBe(['a']);
});

it('carries application keys on a block through a save', function (): void {
    $page = page([[
        'id' => 'a',
        'type' => 'heading',
        'data' => ['text' => 'Hi'],
        'anchor' => 'intro',
        'created_by' => 7,
    ]]);

    canvas($page)->call('save');

    expect($page->fresh()->blocks[0])
        ->toMatchArray(['anchor' => 'intro', 'created_by' => 7]);
});

it('repairs a block whose data is not an array', function (): void {
    $canvas = canvas(page([['id' => 'a', 'type' => 'heading', 'data' => 'broken']]));

    expect($canvas->get('blocks.0.data'))->toBe([]);
});

it('drops an entry with no type at all', function (): void {
    $canvas = canvas(page([block('a'), ['id' => 'b'], 'nonsense']));

    expect(ids($canvas))->toBe(['a']);
});

/* ── Translation ───────────────────────────────────────── */

it('renders the editor chrome in the application locale', function (): void {
    app()->setLocale('de');

    canvas(page())
        ->assertSee('Layout speichern')
        ->assertDontSee('Save layout');
});

it('hands the canvas a JSON payload of translated strings for the script', function (): void {
    app()->setLocale('tr');

    $html = canvas(page())->html();

    preg_match('/data-fpb-i18n="([^"]*)"/', $html, $matches);

    expect($matches)->not->toBeEmpty('the canvas root must carry data-fpb-i18n for page-builder.js');

    $strings = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

    expect($strings)->toBeArray()
        ->and($strings['confirm_delete'])->toBe('Bu blok silinsin mi? İçeriği de silinir.');

    // The script shows the key for a string it is not handed, so every English key has to
    // arrive, translated or not.
    expect(array_keys($strings))->toEqualCanonicalizing(array_keys(require __DIR__.'/../../resources/lang/en/js.php'));
});

it('names blocks in the editor language on the canvas bar', function (): void {
    app()->setLocale('de');

    canvas(page([block('a')]))
        ->assertSeeHtml('aria-label="Überschrift bearbeiten"')
        ->assertSeeHtml('aria-label="Überschrift nach oben"');
});

it('words the quick align buttons as whole phrases in every language', function (): void {
    app()->setLocale('ru');

    canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->assertSeeHtml('title="По левому краю"')
        ->assertSeeHtml('title="По центру"')
        ->assertDontSee('По По');
});

it('shows the anchor hint as code, with a placeholder the field accepts', function (): void {
    canvas(page([block('a')]))
        ->call('selectBlock', 'a')
        ->assertSeeHtml('Link to this block with <code>#intro</code>.')
        ->assertSeeHtml('placeholder="intro"')
        ->assertDontSeeHtml('&lt;code&gt;');

    // Typing the placeholder has to work. A leading # is refused.
    expect(BlockTree::isValidAnchor('intro'))->toBeTrue()
        ->and(BlockTree::isValidAnchor('#intro'))->toBeFalse();
});

it('names a retired type as code in the placeholder that replaces it', function (): void {
    canvas(page([block('gone', 'retired')]))
        ->assertSeeHtml('This page holds a <code>retired</code> block');
});

it('turns a paste down in the editor language', function (): void {
    app()->setLocale('de');

    canvas(page([block('a')]))
        ->call('pasteBlocks', json_encode(['fpb' => 1, 'blocks' => []]))
        ->assertNotified('In der Zwischenablage ist nichts, was diese Seite verwenden kann.');
});

it('warns before the form editor in a handler the browser can run', function (): void {
    app()->setLocale('fr');

    $html = canvas(page([block('a'), [...block('b'), 'parent' => 'a', 'slot' => 'col-0', 'position' => 0]]))->html();

    // Blade leaves directives inside a component's attributes alone, so an @js() there
    // reached the browser as text, the handler did not compile, and the link opened with no
    // warning at all.
    preg_match('/x-on:click="if \(! confirm\((\'.*?\')\)\) \{/', $html, $matches);

    expect($html)->not->toContain('@js(')
        ->and($matches)->not->toBeEmpty('the form editor link must confirm before it opens')
        ->and(json_decode('"'.substr($matches[1], 1, -1).'"'))->toBe(__('page-builder::chrome.form_editor_columns_warning'));
});
