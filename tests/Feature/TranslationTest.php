<?php

use CarlJanzell\FilamentPageBuilder\PageBuilderServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * English is the source of truth for this package: every group under resources/lang/en has to
 * exist, and every other locale has to carry exactly its keys. A missing key is the failure
 * mode worth guarding, because Laravel renders it as the literal `page-builder::blocks.
 * heading.label` in the editor, which reads far worse than an untranslated English word.
 *
 * Placeholders get their own check: a translator who drops `:label` produces a sentence with a
 * hole in it, and nothing else in the suite would notice.
 */

/** The translation groups the package reads. */
const FPB_GROUPS = ['blocks', 'chrome', 'js', 'style'];

/** Locales the package ships translations for. */
const FPB_LOCALES = ['az', 'de', 'es', 'fr', 'ru', 'tr'];

/**
 * Absolute paths of every shipped translation file, keyed "locale/group".
 *
 * @return array<string, string>
 */
function translationFiles(): array
{
    $root = __DIR__.'/../../resources/lang';

    $files = [];

    foreach (File::directories($root) as $localeDir) {
        $locale = basename($localeDir);

        foreach (FPB_GROUPS as $group) {
            $path = $localeDir.'/'.$group.'.php';

            if (File::exists($path)) {
                $files[$locale.'/'.$group] = $path;
            }
        }
    }

    ksort($files);

    return $files;
}

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

it('gives every locale exactly the keys English has', function (string $locale, string $group): void {
    $root = __DIR__.'/../../resources/lang';

    $english = readTranslations($root.'/en/'.$group.'.php');
    $translated = readTranslations($root.'/'.$locale.'/'.$group.'.php');

    $missing = array_diff(array_keys($english), array_keys($translated));
    $extra = array_diff(array_keys($translated), array_keys($english));

    expect($missing)->toBe([], "[{$locale}/{$group}] is missing: ".implode(', ', $missing))
        ->and($extra)->toBe([], "[{$locale}/{$group}] has keys English does not: ".implode(', ', $extra));
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
        if (! str_contains($value, ':')) {
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
    $root = dirname(__DIR__, 2);
    $referenced = [];

    // Every key the package asks for, straight out of the source that asks for it. A key
    // missing from all seven locales still satisfies the parity tests above, so nothing else
    // in the suite would catch it — this is the only check that does.
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

    expect($referenced)->not->toBeEmpty('the scan should find keys the package reads');

    ksort($referenced);

    return $referenced;
});
