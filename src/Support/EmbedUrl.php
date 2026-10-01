<?php

namespace CarlJanzell\FilamentPageBuilder\Support;

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use Throwable;

/**
 * Turn whatever an editor pastes into an address a provider will actually let us frame.
 *
 * Editors paste the link in their address bar — `youtube.com/watch?v=…`, a `youtu.be`
 * share link, a Google Maps place — and every one of those refuses to load in an iframe:
 * the provider only allows its dedicated embed address. Storing the pasted link and
 * framing it as-is is why an embed showed an empty "refused to connect" box. The link is
 * kept as typed (so the editor recognises it) and translated to the embed address every
 * time it is rendered, which also lets start time, autoplay and looping be options rather
 * than query-string surgery.
 *
 * Nothing outside the host allowlist is ever framed.
 */
class EmbedUrl
{
    /**
     * Hosts that may be framed, and their subdomains.
     *
     * @var array<int, string>
     */
    public const HOSTS = [
        'youtube.com',
        'youtube-nocookie.com',
        'youtu.be',
        'vimeo.com',
        'player.vimeo.com',
        'www.google.com',
        'maps.google.com',
        'docs.google.com',
        'drive.google.com',
        'calendar.google.com',
        'facebook.com',
        'open.spotify.com',
        'canva.com',
    ];

    /**
     * Every host an embed may point at: the shipped list plus the application's own.
     *
     * @return array<int, string>
     */
    public static function hosts(): array
    {
        try {
            $extra = app(BlockRegistry::class)->embedHosts();
        } catch (Throwable) {
            $extra = [];
        }

        return array_values(array_unique([...self::HOSTS, ...$extra]));
    }

    public static function isAllowedHost(?string $host): bool
    {
        $host = strtolower((string) $host);

        if ($host === '') {
            return false;
        }

        foreach (self::hosts() as $allowed) {
            $allowed = strtolower($allowed);

            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The `src` of a pasted `<iframe>` snippet, or the input unchanged.
     *
     * Providers' "Share → Embed" buttons hand out a whole iframe tag, and pasting that
     * into a URL field is the most natural thing in the world.
     */
    public static function fromSnippet(string $input): string
    {
        $input = trim($input);

        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', $input, $match)) {
            return trim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5));
        }

        return $input;
    }

    /**
     * What a pasted address points at, or null when it cannot be framed.
     *
     * @return array{provider: string, url: string, id?: string, hash?: string, start?: int}|null
     */
    public static function resolve(mixed $input): ?array
    {
        if (! is_string($input) || trim($input) === '') {
            return null;
        }

        $url = self::fromSnippet($input);

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host']) || ! SafeUrl::allows($url)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $bare = preg_replace('/^(www\.|m\.|music\.)/', '', $host) ?? $host;
        $path = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $query);

        if (! self::isAllowedHost($host)) {
            return null;
        }

        return match (true) {
            in_array($bare, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true) => self::youtube($bare, $path, $query),
            in_array($bare, ['vimeo.com', 'player.vimeo.com'], true) => self::vimeo($bare, $path, $query),
            in_array($bare, ['google.com', 'maps.google.com'], true) && str_starts_with($path, '/maps') => self::maps($path, $query, $url),
            $bare === 'docs.google.com', $bare === 'drive.google.com', $bare === 'calendar.google.com' => self::google($bare, $path, $url),
            $bare === 'facebook.com' || str_ends_with($bare, '.facebook.com') => self::facebook($path, $url),
            $bare === 'open.spotify.com' => self::spotify($path),
            $bare === 'canva.com' => self::canva($path),
            in_array($bare, ['google.com'], true) => null,
            default => str_starts_with(strtolower($url), 'https://') ? ['provider' => 'generic', 'url' => $url] : null,
        };
    }

    /**
     * A sentence telling the editor what to paste instead, for an address we refuse.
     */
    public static function problem(mixed $input): ?string
    {
        if (! is_string($input) || trim($input) === '' || self::resolve($input) !== null) {
            return null;
        }

        $url = strtolower(self::fromSnippet($input));

        return match (true) {
            str_contains($url, 'maps.app.goo.gl'), str_contains($url, 'goo.gl/maps') => __('page-builder::blocks.problems.short_map'),
            str_contains($url, '/maps/dir') => __('page-builder::blocks.problems.directions'),
            str_contains($url, 'forms.gle') => __('page-builder::blocks.problems.short_form'),
            default => __('page-builder::blocks.problems.unknown'),
        };
    }

    /**
     * The iframe address for a resolved embed, with playback options applied.
     *
     * @param  array<string, mixed>  $embed
     * @param  array{start?: ?int, end?: ?int, autoplay?: bool, mute?: bool, loop?: bool, controls?: bool, background?: bool}  $options
     */
    public static function src(array $embed, array $options = []): string
    {
        $start = (int) ($options['start'] ?? 0) ?: (int) ($embed['start'] ?? 0);
        $end = isset($options['end']) && (int) $options['end'] > $start ? (int) $options['end'] : null;
        $autoplay = (bool) ($options['autoplay'] ?? false);
        $mute = (bool) ($options['mute'] ?? $autoplay);
        $loop = (bool) ($options['loop'] ?? false);
        $controls = (bool) ($options['controls'] ?? true);
        $background = (bool) ($options['background'] ?? false);

        if ($embed['provider'] === 'youtube') {
            $params = array_filter([
                'start' => $start ?: null,
                'end' => $end,
                'autoplay' => $autoplay ? 1 : null,
                'mute' => $mute ? 1 : null,
                'loop' => $loop ? 1 : null,
                'playlist' => $loop ? $embed['id'] : null,
                'controls' => $controls && ! $background ? null : 0,
                'playsinline' => 1,
                'rel' => 0,
                'disablekb' => $background ? 1 : null,
                'iv_load_policy' => $background ? 3 : null,
            ], fn (mixed $value): bool => $value !== null);

            return 'https://www.youtube-nocookie.com/embed/'.$embed['id'].'?'.http_build_query($params);
        }

        if ($embed['provider'] === 'vimeo') {
            $params = array_filter([
                'h' => $embed['hash'] ?? null,
                'autoplay' => $autoplay ? 1 : null,
                'muted' => $mute ? 1 : null,
                'loop' => $loop ? 1 : null,
                'controls' => $controls && ! $background ? null : 0,
                'background' => $background ? 1 : null,
                'dnt' => 1,
            ], fn (mixed $value): bool => $value !== null);

            return 'https://player.vimeo.com/video/'.$embed['id'].'?'.http_build_query($params).($start ? '#t='.$start.'s' : '');
        }

        return $embed['url'];
    }

    /**
     * Seconds from a time an editor types: `90`, `1:30`, `01:02:03`, `1m30s`, `2h`.
     */
    public static function seconds(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            return max(0, (int) $value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = strtolower(trim($value));

        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        if (preg_match('/^(\d{1,2}:)?\d{1,3}:\d{1,2}$/', $value)) {
            $seconds = 0;

            foreach (explode(':', $value) as $part) {
                $seconds = $seconds * 60 + (int) $part;
            }

            return $seconds;
        }

        if (preg_match('/^(?:(\d+)h)?\s*(?:(\d+)m)?\s*(?:(\d+)s)?$/', $value, $match) && $value !== '') {
            return ((int) ($match[1] ?? 0)) * 3600 + ((int) ($match[2] ?? 0)) * 60 + (int) ($match[3] ?? 0);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{provider: string, url: string, id: string, start?: int}|null
     */
    protected static function youtube(string $host, string $path, array $query): ?array
    {
        $id = null;

        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif ($path === '/watch' || $path === '/watch/') {
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } elseif (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $match)) {
            $id = $match[1];
        }

        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return null;
        }

        $start = self::seconds($query['t'] ?? $query['start'] ?? null);

        return array_filter([
            'provider' => 'youtube',
            'id' => $id,
            'url' => 'https://www.youtube.com/watch?v='.$id,
            'start' => $start ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{provider: string, url: string, id: string, hash?: string}|null
     */
    protected static function vimeo(string $host, string $path, array $query): ?array
    {
        $pattern = $host === 'player.vimeo.com'
            ? '#^/video/(\d+)#'
            : '#^/(?:channels/[^/]+/|groups/[^/]+/videos/|video/)?(\d+)(?:/([0-9a-f]+))?#';

        if (! preg_match($pattern, $path, $match)) {
            return null;
        }

        $hash = $match[2] ?? (is_string($query['h'] ?? null) ? $query['h'] : null);

        return array_filter([
            'provider' => 'vimeo',
            'id' => $match[1],
            'hash' => is_string($hash) && preg_match('/^[0-9a-f]+$/', $hash) ? $hash : null,
            'url' => 'https://vimeo.com/'.$match[1],
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{provider: string, url: string}|null
     */
    protected static function maps(string $path, array $query, string $url): ?array
    {
        if (str_starts_with($path, '/maps/embed')) {
            return ['provider' => 'maps', 'url' => preg_replace('#^http://#i', 'https://', $url)];
        }

        if (str_starts_with($path, '/maps/dir')) {
            return null;
        }

        $q = is_string($query['q'] ?? null) ? $query['q'] : (is_string($query['query'] ?? null) ? $query['query'] : null);
        $zoom = null;

        if ($q === null && preg_match('#^/maps/(?:place|search)/([^/@]+)#', $path, $match)) {
            $q = str_replace('+', ' ', urldecode($match[1]));
        }

        if (preg_match('#@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)(?:,(\d+(?:\.\d+)?)z)?#', $path, $match)) {
            $q ??= $match[1].','.$match[2];
            $zoom = isset($match[3]) ? (int) $match[3] : null;
        }

        if ($q === null || trim($q) === '') {
            return null;
        }

        return [
            'provider' => 'maps',
            'url' => 'https://www.google.com/maps?'.http_build_query(array_filter([
                'q' => $q,
                'z' => $zoom,
                'output' => 'embed',
            ], fn (mixed $value): bool => $value !== null)),
        ];
    }

    /**
     * @return array{provider: string, url: string}|null
     */
    protected static function google(string $host, string $path, string $url): ?array
    {
        if ($host === 'calendar.google.com') {
            return str_starts_with($path, '/calendar/embed') ? ['provider' => 'google', 'url' => $url] : null;
        }

        if ($host === 'drive.google.com') {
            return preg_match('#^/file/d/([A-Za-z0-9_-]+)#', $path, $match)
                ? ['provider' => 'google', 'url' => 'https://drive.google.com/file/d/'.$match[1].'/preview']
                : null;
        }

        if (preg_match('#^/forms/d/(e/)?([A-Za-z0-9_-]+)#', $path, $match)) {
            return ['provider' => 'google', 'url' => 'https://docs.google.com/forms/d/'.$match[1].$match[2].'/viewform?embedded=true'];
        }

        if (preg_match('#^/(document|presentation|spreadsheets)/d/e/([A-Za-z0-9_-]+)/(pub\w*)#', $path, $match)) {
            return ['provider' => 'google', 'url' => 'https://docs.google.com/'.$match[1].'/d/e/'.$match[2].'/'.$match[3].(str_contains($url, '?') ? '&' : '?').'embedded=true'];
        }

        if (preg_match('#^/(document|presentation|spreadsheets)/d/([A-Za-z0-9_-]+)#', $path, $match)) {
            $mode = $match[1] === 'presentation' ? 'embed' : 'preview';

            return ['provider' => 'google', 'url' => 'https://docs.google.com/'.$match[1].'/d/'.$match[2].'/'.$mode];
        }

        return null;
    }

    /**
     * @return array{provider: string, url: string}
     */
    protected static function facebook(string $path, string $url): array
    {
        if (str_starts_with($path, '/plugins/')) {
            return ['provider' => 'facebook', 'url' => $url];
        }

        $plugin = preg_match('#/(videos|watch|reel)\b#', $path) ? 'video' : 'post';

        return [
            'provider' => 'facebook',
            'url' => 'https://www.facebook.com/plugins/'.$plugin.'.php?'.http_build_query(['href' => $url, 'show_text' => 'false']),
        ];
    }

    /**
     * @return array{provider: string, url: string}|null
     */
    protected static function spotify(string $path): ?array
    {
        if (! preg_match('#^/(?:embed/)?(track|album|playlist|episode|show|artist)/([A-Za-z0-9]+)#', $path, $match)) {
            return null;
        }

        return ['provider' => 'spotify', 'url' => 'https://open.spotify.com/embed/'.$match[1].'/'.$match[2]];
    }

    /**
     * @return array{provider: string, url: string}|null
     */
    protected static function canva(string $path): ?array
    {
        if (! preg_match('#^/design/([A-Za-z0-9_-]+)(?:/([A-Za-z0-9_-]+))?#', $path, $match)) {
            return null;
        }

        return ['provider' => 'canva', 'url' => 'https://www.canva.com/design/'.$match[1].'/'.(($match[2] ?? '') !== '' && $match[2] !== 'view' && $match[2] !== 'edit' ? $match[2].'/' : '').'view?embed'];
    }
}
