<?php

namespace CarlJanzell\FilamentPageBuilder;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use InvalidArgumentException;
use Throwable;

/**
 * `[name key="value"]` tags that call into the application to fetch live data.
 *
 * An editor types a shortcode where the content should appear — in a text block, a
 * heading, custom code, or a Shortcode block of its own — and the page asks the
 * application for the markup at render time. The application registers each one as a
 * callback, so it can query models, read settings or render a view; that callback is
 * the only thing that decides what a shortcode can reach, which keeps "call data from
 * the backend" from becoming "run anything the editor typed".
 *
 * Literal text around a shortcode is escaped; a callback's return value is trusted
 * markup, so callbacks must escape anything they echo back from `$attributes`.
 */
class Shortcodes
{
    /**
     * @var array<string, array{callback: Closure, description: ?string, example: ?string}>
     */
    protected array $codes = [];

    /**
     * @param  callable(array<int|string, string>, ?string): mixed  $callback
     */
    public function register(string $name, callable $callback, ?string $description = null, ?string $example = null): static
    {
        $name = strtolower($name);

        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $name)) {
            throw new InvalidArgumentException("[{$name}] is not a valid shortcode name. Use letters, numbers, - and _.");
        }

        $this->codes[$name] = [
            'callback' => Closure::fromCallable($callback),
            'description' => $description,
            'example' => $example ?? "[{$name}]",
        ];

        return $this;
    }

    public function has(?string $name): bool
    {
        return is_string($name) && isset($this->codes[strtolower($name)]);
    }

    /**
     * @return array<string, array{description: ?string, example: ?string}>
     */
    public function all(): array
    {
        return array_map(
            fn (array $code): array => ['description' => $code['description'], 'example' => $code['example']],
            $this->codes,
        );
    }

    /**
     * Run one shortcode and return its markup.
     *
     * A failing callback must not take the page down with it: the error is reported, the
     * public page gets nothing, and the canvas says which shortcode broke and why.
     *
     * @param  array<int|string, string>  $attributes
     */
    public function render(string $name, array $attributes = [], ?string $content = null): string
    {
        $code = $this->codes[strtolower($name)] ?? null;

        if ($code === null) {
            return e('['.$name.']');
        }

        try {
            $result = ($code['callback'])($attributes, $content);
        } catch (Throwable $exception) {
            report($exception);

            return PageBuilder::isEditing()
                ? '<span class="fpb-shortcode-error">['.e($name).'] failed: '.e($exception->getMessage()).'</span>'
                : '';
        }

        return match (true) {
            $result instanceof Htmlable => $result->toHtml(),
            $result instanceof Renderable => $result->render(),
            is_scalar($result) => (string) $result,
            default => '',
        };
    }

    /**
     * Replace every registered shortcode in `$text` with its output.
     *
     * `$escape` says whether the text around the shortcodes is plain text (escape it) or
     * already markup (leave it). Unknown names stay as typed, so a bracket in ordinary
     * prose is never eaten, and `[[name]]` prints a literal `[name]`.
     */
    public function expand(?string $text, bool $escape = true): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $pattern = '/\[(\[?)([A-Za-z][A-Za-z0-9_-]*)((?:\s[^\]]*)?)\](?:(.*?)\[\/\2\])?(\]?)/s';
        $output = '';
        $offset = 0;

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            [$whole, $at] = $match[0];
            $name = $match[2][0];
            $known = $this->has($name);

            $output .= $this->literal(substr($text, $offset, $at - $offset), $escape);
            $offset = $at + strlen($whole);

            if ($match[1][0] === '[' && $match[5][0] === ']') {
                $output .= $this->literal(substr($whole, 1, -1), $escape);

                continue;
            }

            if (! $known) {
                $output .= $this->literal($whole, $escape);

                continue;
            }

            $content = isset($match[4]) && $match[4][1] !== -1 ? $match[4][0] : null;

            $output .= $this->literal($match[1][0], $escape)
                .$this->render($name, self::parseAttributes($match[3][0]), $content)
                .$this->literal($match[5][0], $escape);
        }

        return $output.$this->literal(substr($text, $offset), $escape);
    }

    /**
     * Whether `$text` holds a shortcode this registry knows.
     */
    public function mentions(?string $text): bool
    {
        if (! is_string($text) || ! str_contains($text, '[')) {
            return false;
        }

        preg_match_all('/\[([A-Za-z][A-Za-z0-9_-]*)/', $text, $matches);

        foreach ($matches[1] as $name) {
            if ($this->has($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * `count="3" category='news' featured` → `['count' => '3', 'category' => 'news', 0 => 'featured']`.
     *
     * @return array<int|string, string>
     */
    public static function parseAttributes(string $raw): array
    {
        $attributes = [];
        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"|([\w-]+)\s*=\s*\'([^\']*)\'|([\w-]+)\s*=\s*([^\s"\']+)|"([^"]*)"|\'([^\']*)\'|(\S+)/';

        preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            match (true) {
                ($match[1] ?? '') !== '' => $attributes[strtolower($match[1])] = html_entity_decode($match[2]),
                ($match[3] ?? '') !== '' => $attributes[strtolower($match[3])] = html_entity_decode($match[4]),
                ($match[5] ?? '') !== '' => $attributes[strtolower($match[5])] = html_entity_decode($match[6]),
                ($match[7] ?? '') !== '' => $attributes[] = html_entity_decode($match[7]),
                ($match[8] ?? '') !== '' => $attributes[] = html_entity_decode($match[8]),
                default => $attributes[] = html_entity_decode($match[9] ?? ''),
            };
        }

        return $attributes;
    }

    protected function literal(string $text, bool $escape): string
    {
        return $escape ? e($text) : $text;
    }
}
