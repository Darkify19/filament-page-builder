<?php

use CarlJanzell\FilamentPageBuilder\PageBuilder;
use CarlJanzell\FilamentPageBuilder\Shortcodes;
use Illuminate\Support\HtmlString;

beforeEach(function (): void {
    PageBuilder::idle();

    $this->codes = (new Shortcodes)
        ->register('greet', fn (array $attributes): string => '<b>Hi '.e($attributes['name'] ?? 'you').'</b>')
        ->register('upper', fn (array $attributes, ?string $content): HtmlString => new HtmlString(strtoupper(e($content ?? ''))))
        ->register('broken', fn (): string => throw new RuntimeException('database is down'));
});

it('runs a registered shortcode and escapes the text around it', function (): void {
    expect($this->codes->expand('<i>x</i> [greet name="Ana & Co"]!'))
        ->toBe('&lt;i&gt;x&lt;/i&gt; <b>Hi Ana &amp; Co</b>!');
});

it('leaves brackets it does not know alone', function (): void {
    expect($this->codes->expand('See [note] and [1]'))->toBe('See [note] and [1]');
});

it('prints a doubled bracket as a literal shortcode', function (): void {
    expect($this->codes->expand('Type [[greet]] to say hi'))->toBe('Type [greet] to say hi');
});

it('passes enclosed content to the callback', function (): void {
    expect($this->codes->expand('[upper]quiet[/upper] words'))->toBe('QUIET words');
});

it('keeps markup around shortcodes when asked to', function (): void {
    expect($this->codes->expand('<p>[greet name=Bo]</p>', escape: false))->toBe('<p><b>Hi Bo</b></p>');
});

it('parses every attribute style', function (): void {
    expect(Shortcodes::parseAttributes(' count="3" Category=\'news & views\' size=large featured'))
        ->toBe(['count' => '3', 'category' => 'news & views', 'size' => 'large', 0 => 'featured']);
});

it('does not let a failing shortcode take the page down', function (): void {
    expect($this->codes->expand('Before [broken] after'))->toBe('Before  after');
});

it('knows when text holds a shortcode it can run', function (): void {
    expect($this->codes->mentions('Hello [greet]'))->toBeTrue()
        ->and($this->codes->mentions('Hello [nobody]'))->toBeFalse()
        ->and($this->codes->mentions(null))->toBeFalse();
});

it('refuses names that could not be typed back', function (): void {
    (new Shortcodes)->register('no spaces', fn (): string => '');
})->throws(InvalidArgumentException::class);
