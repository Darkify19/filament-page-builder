<?php

namespace CarlJanzell\FilamentPageBuilder\Support;

/**
 * Links an editor may put on a public page.
 *
 * Button and embed blocks are visible to everyone by default. A `javascript:` or
 * `data:` href would run in the visitor's browser, so anything that is not an
 * http(s) URL, a mailto/tel, a hash, or a same-site path is dropped.
 */
class SafeUrl
{
    /**
     * @var array<int, string>
     */
    public const SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function allows(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (str_starts_with($url, '#') || (str_starts_with($url, '/') && ! str_starts_with($url, '//'))) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, self::SCHEMES, true);
    }

    /**
     * A href that is safe to print, or `#` when the stored value is empty or refused.
     */
    public static function href(?string $url): string
    {
        return self::allows($url) ? $url : '#';
    }
}
