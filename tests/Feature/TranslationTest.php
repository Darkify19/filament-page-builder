<?php

use CarlJanzell\FilamentPageBuilder\BlockRegistries;
use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use CarlJanzell\FilamentPageBuilder\PageBuilder;
use CarlJanzell\FilamentPageBuilder\PageBuilderServiceProvider;
use CarlJanzell\FilamentPageBuilder\Shortcodes;
use Filament\Panel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * English is the source of truth for this package: every group under resources/lang/en has to
 * exist, and every other locale is a translation of it.
 *
 * A locale may lag behind. Laravel falls back to the fallback locale key by key, and
 * `DesignPage::canvasStrings()` does the same for the script's strings, so a new English
 * string reads in English until someone translates it. A locale may not get ahead, though: a
 * key English does not have is a typo or a leftover, and nothing would ever read it.
 *
 * Placeholders get their own check: a translator who drops `:label` produces a sentence with a
 * hole in it, and nothing else in the suite would notice.
 */

/** The translation groups the package reads. */
const FPB_GROUPS = ['blocks', 'chrome', 'js', 'style'];

/** Locales the package ships translations for. */
const FPB_LOCALES = ['az', 'de', 'es', 'fr', 'ru', 'tr'];

/**
 * Read a translation file and flatten it to "dot.key" => value.
 *
 * @return array<string, string>
 */
function readTranslations(string $path): array
{
    $strings = require $path;

    $flat = [];

    $walk = function (array $carry, string $prefix) use (&$walk, &$flat): void {
        foreach ($carry as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $walk($value, $path);

                continue;
            }

            $flat[$path] = (string) $value;
        }
    };

    $walk($strings, '');

    return $flat;
}

/**
 * The placeholder names in a string, e.g. [':label', ':count'].
 *
 * @return array<int, string>
 */
function placeholdersIn(string $value): array
{
    // A colon preceded by a letter or digit belongs to the value, not to a placeholder. The
    // time hint reads "m:ss" in English, "d:dk" in Turkish and "dəqiqə:saniyə" in Azerbaijani,
    // and \w is ASCII-only, so this has to be explicit about letters and digits rather than
    // relying on \w to reject them.
    preg_match_all('/(?<![\p{L}\p{N}_]):([a-z_]+)/u', $value, $matches);

    return Arr::sort($matches[1]);
}

/**
 * Every key the package asks for, straight out of the source that asks for it, keyed
 * "group.key" => [group, key].
 *
 * @return array<string, array{string, string}>
 */
function referencedKeys(): array
{
    $root = dirname(__DIR__, 2);
    $referenced = [];

    // A key missing from all seven locales still satisfies the per-locale checks below, so
    // the source is the only thing that can say a key should exist.
    foreach (['src', 'resources'] as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            preg_match_all(
                '/\bpage-builder::(?!components\.)(chrome|blocks|style|js)\.([a-z0-9_.]+)/',
                (string) file_get_contents($file->getPathname()),
                $matches
            );

            foreach ($matches[1] as $index => $group) {
                $referenced[$group.'.'.$matches[2][$index]] = [$group, $matches[2][$index]];
            }
        }
    }

    // The script asks through `t('key')`, which reads the js group without the prefix.
    preg_match_all("/\\bt\\('([a-z0-9_]+)'/", (string) file_get_contents($root.'/resources/js/page-builder.js'), $matches);

    foreach ($matches[1] as $key) {
        $referenced['js.'.$key] = ['js', $key];
    }

    ksort($referenced);

    return $referenced;
}

it('ships an English file for every group the package reads', function (string $group): void {
    expect(File::exists(__DIR__.'/../../resources/lang/en/'.$group.'.php'))->toBeTrue();
})->with(FPB_GROUPS);

it('ships a file for every group in every locale it claims to support', function (string $locale): void {
    foreach (FPB_GROUPS as $group) {
        $path = __DIR__.'/../../resources/lang/'.$locale.'/'.$group.'.php';

        expect(File::exists($path))->toBeTrue("resources/lang/{$locale}/{$group}.php is missing");

        // Existence is not parseable. A French apostrophe in a single-quoted string
        // ("L'année en cours.") leaves a file that exists, that the parity checks can
        // still compare, and that fatals the moment Laravel loads it. Parse every file.
        expect(readTranslations($path))->toBeArray();
    }
})->with(FPB_LOCALES);

it('gives no locale a key English does not have', function (string $locale, string $group): void {
    $root = __DIR__.'/../../resources/lang';

    $english = readTranslations($root.'/en/'.$group.'.php');
    $translated = readTranslations($root.'/'.$locale.'/'.$group.'.php');

    $extra = array_values(array_diff(array_keys($translated), array_keys($english)));

    expect($extra)->toBe([], "[{$locale}/{$group}] has keys English does not: ".implode(', ', $extra));
})->with(function (): array {
    return collect(FPB_LOCALES)
        ->crossJoin(FPB_GROUPS)
        ->map(fn (array $pair): array => [$pair[0], $pair[1]])
        ->all();
});

it('gives every locale the same placeholders English has', function (string $locale, string $group): void {
    $root = __DIR__.'/../../resources/lang';

    $english = readTranslations($root.'/en/'.$group.'.php');
    $translated = readTranslations($root.'/'.$locale.'/'.$group.'.php');

    foreach ($english as $key => $value) {
        if (! str_contains($value, ':') || ! array_key_exists($key, $translated)) {
            continue;
        }

        expect(placeholdersIn($translated[$key]))
            ->toBe(placeholdersIn($value), "[{$locale}/{$group}] changed the placeholders in [{$key}]");
    }
})->with(function (): array {
    return collect(FPB_LOCALES)
        ->crossJoin(FPB_GROUPS)
        ->map(fn (array $pair): array => [$pair[0], $pair[1]])
        ->all();
});

it('resolves a translated string through the package namespace', function (): void {
    app()->setLocale('tr');

    expect(__('page-builder::blocks.heading.label'))->toBe('Başlık')
        ->and(__('page-builder::chrome.delete'))->toBe('Sil')
        ->and(__('page-builder::js.confirm_delete'))->toBe('Bu blok silinsin mi? İçeriği de silinir.');
});

it('resolves a string whose placeholders it fills in', function (): void {
    app()->setLocale('de');

    expect(__('page-builder::chrome.add_block', ['label' => 'Überschrift']))
        ->toBe('Überschrift hinzufügen');
});

it('falls back to English for a locale the package does not ship', function (): void {
    app()->setLocale('kk'); // Kazakh: not a shipped locale.

    expect(__('page-builder::blocks.heading.label'))->toBe('Heading');
});

it('serves the JS group as a whole array for the canvas', function (): void {
    $strings = __('page-builder::js');

    expect($strings)->toBeArray()
        ->and($strings)->toHaveKey('confirm_delete')
        ->and($strings)->toHaveKey('place_column_n')
        ->and($strings['place_column_n'])->toBeString();
});

it('falls back to English key by key for a string a locale has not translated yet', function (): void {
    // A locale that has translated one string of the js group and nothing else.
    app('translator')->addLines(['js.confirm_delete' => 'Löschen?'], 'xx', 'page-builder');
    app()->setLocale('xx');

    expect(__('page-builder::js.confirm_delete'))->toBe('Löschen?')
        ->and(__('page-builder::js.add_here'))->toBe('Add here');
});

it('fills the gaps in the script\'s strings with English', function (): void {
    // Asking for the whole group gets the locale's file as it stands, with none of the
    // key-by-key fallback above, so this is the one place a gap would reach an editor.
    app('translator')->addLines(['js.confirm_delete' => 'Löschen?'], 'xx', 'page-builder');
    app()->setLocale('xx');

    $strings = canvas(page())->instance()->canvasStrings();

    expect($strings['confirm_delete'])->toBe('Löschen?')
        ->and($strings['add_here'])->toBe('Add here')
        ->and(array_keys($strings))->toEqualCanonicalizing(array_keys(require __DIR__.'/../../resources/lang/en/js.php'));
});

it('escapes a translated line but not the markup put into it', function (): void {
    app('translator')->addLines(['chrome.anchor_hint' => '<b>Link</b> with :anchor.'], 'xx', 'page-builder');
    app()->setLocale('xx');

    expect((string) PageBuilder::lineWithMarkup('page-builder::chrome.anchor_hint', ['anchor' => '<code>#intro</code>']))
        ->toBe('&lt;b&gt;Link&lt;/b&gt; with <code>#intro</code>.');
});

it('translates a shortcode description when the list is shown', function (): void {
    $codes = (new Shortcodes)->register('year', fn (): string => '2026', fn (): string => __('page-builder::blocks.shortcode.builtin_year'));

    app()->setLocale('de');

    expect($codes->all()['year']['description'])->toBe('Das aktuelle Jahr.');
});

it('describes the built-in shortcodes in the request\'s language, not the one at boot', function (): void {
    // The panel is built while the application boots, before a locale middleware runs.
    FilamentPageBuilderPlugin::make()->register(Panel::make()->id('i18n'));

    app()->setLocale('de');

    expect(app(BlockRegistries::class)->for('i18n')->shortcodes()->all()['year']['description'])
        ->toBe('Das aktuelle Jahr.');
});

it('publishes its translations so an application can override one sentence', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        PageBuilderServiceProvider::class,
        'page-builder-translations'
    );

    // pathsToPublish() is keyed by source. The application copies the whole lang directory
    // into its own lang/vendor/page-builder, and Laravel then merges a published file over
    // the package's key by key — so correcting one sentence does not mean shipping the other
    // three groups, and does not mean forking the package.
    $target = app()->langPath('vendor/page-builder');

    // ServiceProvider's publish registry is static and shared across the suite, so pick the
    // entry that is a real directory rather than trusting the first one.
    $sources = array_values(array_filter(array_keys($paths), 'is_dir'));

    expect(array_values($paths))->toContain($target)
        ->and($sources)->not->toBeEmpty()
        ->and(is_dir($sources[0].DIRECTORY_SEPARATOR.'en'))->toBeTrue('the published source must hold the English files');

    // The thing this all exists for: a key that resolves to a sentence, not a dotted path.
    expect(__('page-builder::blocks.heading.label'))->not->toBe('page-builder::blocks.heading.label');
});

it('defines every key the package asks for at runtime', function (string $group, string $path): void {
    $english = require __DIR__.'/../../resources/lang/en/'.$group.'.php';

    expect(Arr::has($english, $path))
        ->toBeTrue("page-builder::{$group}.{$path} is asked for but not defined in English");

    expect(Arr::get($english, $path))->toBeString();
})->with(function (): array {
    $referenced = referencedKeys();

    expect($referenced)->not->toBeEmpty('the scan should find keys the package reads');

    return $referenced;
});

it('reads every key English defines', function (string $group): void {
    $unread = array_values(array_diff(
        array_keys(readTranslations(__DIR__.'/../../resources/lang/en/'.$group.'.php')),
        array_map(fn (array $pair): string => $pair[1], array_filter(referencedKeys(), fn (array $pair): bool => $pair[0] === $group)),
    ));

    // A string nothing reads still gets translated six times, and a translator fixing it
    // changes nothing on screen. Build an option label from a key fragment and this fails,
    // which is the point: a key the scan cannot see is one a translator cannot trust.
    expect($unread)->toBe([], "[{$group}] defines keys nothing reads: ".implode(', ', $unread));
})->with(FPB_GROUPS);

it('shows a shortcode it does not know as code on the canvas', function (): void {
    PageBuilder::editing('x', 'shortcode');

    try {
        $html = view('page-builder::components.shortcode', ['data' => ['code' => '[nope] & co']])->render();
    } finally {
        PageBuilder::idle();
    }

    expect($html)->toContain('No registered shortcode found in <code>[nope] &amp; co</code>.');
});
