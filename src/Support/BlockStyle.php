<?php

namespace CarlJanzell\FilamentPageBuilder\Support;

/**
 * Free-form styling for one block: spacing, typography, colour, background, border, size.
 *
 * Stored on the block as `style`, beside the brand `settings` tokens. Tokens are names the
 * site's stylesheet interprets; this is the escape hatch editors asked for when a token
 * list could not say "24px of padding on the left only" or "this exact maroon".
 *
 * Nothing here is ever written into the page as the editor typed it. Every value is
 * checked against its kind (a number in range, a unit from a list, a colour in one of the
 * forms a colour picker produces) and anything else is dropped, so the style attribute
 * this compiles to can only ever contain declarations this class built itself. The
 * canvas is a public Livewire surface, and a crafted payload must not be able to smuggle
 * `url(javascript:…)` or `}</style><script>` onto a public page.
 */
class BlockStyle
{
    /**
     * @var array<int, string>
     */
    public const SIDES = ['top', 'right', 'bottom', 'left'];

    /**
     * @var array<int, string>
     */
    public const SPACING_UNITS = ['px', 'rem', 'em', '%', 'vh', 'vw'];

    /**
     * @var array<int, string>
     */
    public const FILE_FIELDS = ['background_image', 'background_video'];

    /**
     * The keys the inspector's Layout tab owns; every other key belongs to the Style tab.
     *
     * @var array<int, string>
     */
    public const LAYOUT_KEYS = [
        'padding_top', 'padding_right', 'padding_bottom', 'padding_left', 'padding_unit',
        'margin_top', 'margin_right', 'margin_bottom', 'margin_left', 'margin_unit',
        'width', 'width_unit', 'max_width', 'max_width_unit', 'min_height', 'min_height_unit',
        'element_align', 'css_class',
    ];

    /**
     * Every key this layer understands, and what a value for it has to be.
     *
     * A list is an enumeration. Otherwise: `length` is a spacing number (margins may be
     * negative), `number:min:max` a bounded number, `color`, `path` (an upload or an
     * http(s) URL), `url` (http(s) only), `bool`, or `classes` (CSS class names).
     *
     * @return array<string, string|array<int, string>>
     */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::SIDES as $side) {
            $fields["padding_{$side}"] = 'number:0:2000';
            $fields["margin_{$side}"] = 'number:-2000:2000';
        }

        return [
            ...$fields,
            'padding_unit' => self::SPACING_UNITS,
            'margin_unit' => self::SPACING_UNITS,
            'text_align' => ['left', 'center', 'right', 'justify'],
            'text_color' => 'color',
            'font_size' => 'number:1:400',
            'font_size_unit' => ['px', 'rem', 'em', 'vw'],
            'font_weight' => ['300', '400', '500', '600', '700', '800', '900'],
            'line_height' => 'number:0.5:5',
            'letter_spacing' => 'number:-20:50',
            'text_transform' => ['uppercase', 'lowercase', 'capitalize', 'none'],
            'background_type' => ['color', 'gradient', 'image', 'video'],
            'background_color' => 'color',
            'gradient_type' => ['linear', 'radial'],
            'gradient_from' => 'color',
            'gradient_to' => 'color',
            'gradient_angle' => 'number:0:360',
            'background_image' => 'path',
            'background_size' => ['cover', 'contain', 'auto'],
            'background_position' => ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'],
            'background_repeat' => 'bool',
            'background_fixed' => 'bool',
            'background_video' => 'path',
            'background_video_url' => 'url',
            'video_start' => 'number:0:86400',
            'video_end' => 'number:0:86400',
            'video_once' => 'bool',
            'video_rate' => ['0.5', '0.75', '1', '1.25', '1.5', '2'],
            'overlay_color' => 'color',
            'overlay_opacity' => 'number:0:100',
            'border_width' => 'number:0:50',
            'border_style' => ['solid', 'dashed', 'dotted', 'double'],
            'border_color' => 'color',
            'border_radius' => 'number:0:500',
            'shadow' => ['sm', 'md', 'lg', 'xl'],
            'opacity' => 'number:0:100',
            'width' => 'number:1:4000',
            'width_unit' => ['%', 'px', 'rem', 'vw'],
            'max_width' => 'number:1:4000',
            'max_width_unit' => ['px', 'rem', '%', 'vw'],
            'min_height' => 'number:0:4000',
            'min_height_unit' => ['px', 'rem', 'vh'],
            'element_align' => ['left', 'center', 'right'],
            'css_class' => 'classes',
        ];
    }

    /**
     * Keep only what this layer understands, in the shape it understands it.
     *
     * Empty values are dropped rather than stored as null, so a block nobody restyled
     * keeps an empty `style` and an untouched field never shadows a token.
     *
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $style): array
    {
        if (! is_array($style)) {
            return [];
        }

        $clean = [];

        foreach (self::fields() as $key => $kind) {
            if (! array_key_exists($key, $style)) {
                continue;
            }

            $value = self::clean($style[$key], $kind);

            if ($value !== null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * One value, cleaned for its kind, or null when it is empty or not acceptable.
     *
     * @param  string|array<int, string>  $kind
     */
    public static function clean(mixed $value, string|array $kind): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($kind)) {
            $value = is_scalar($value) ? (string) $value : null;

            return in_array($value, $kind, true) ? $value : null;
        }

        if (str_starts_with($kind, 'number:')) {
            [, $min, $max] = explode(':', $kind);

            return self::number($value, (float) $min, (float) $max);
        }

        return match ($kind) {
            'color' => self::color($value),
            'path' => self::path($value),
            'url' => self::url($value),
            // Every switch in this layer is off by default, so off is simply not stored.
            'bool' => self::bool($value) ?: null,
            'classes' => self::classes($value),
            default => null,
        };
    }

    public static function number(mixed $value, float $min, float $max): int|float|null
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        $number = round(max($min, min($max, (float) $value)), 2);

        return floor($number) === $number ? (int) $number : $number;
    }

    /**
     * A colour in one of the forms a colour picker writes, or null.
     *
     * Hex (3, 4, 6 or 8 digits), rgb()/rgba() and hsl()/hsla() with plain numbers only.
     * Named colours other than `transparent` are refused: they are harmless, but a free
     * word is exactly the door a crafted value would try first.
     */
    public static function color(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        if ($value === 'transparent') {
            return $value;
        }

        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value)) {
            return $value;
        }

        $number = '\s*-?\d{1,3}(?:\.\d+)?%?\s*';
        $alpha = '(?:\s*[,\/]\s*(?:0|1|0?\.\d+|\d{1,3}%))?\s*';

        if (preg_match('/^(?:rgb|rgba|hsl|hsla)\('.$number.','.$number.','.$number.$alpha.'\)$/', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * A stored upload path or an http(s) URL, for a background image or video.
     */
    public static function path(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->first(fn (mixed $item): bool => is_string($item) && $item !== '');
        }

        if (! is_string($value) || $value === '' || strlen($value) > 2048) {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return self::url($value);
        }

        if (str_contains($value, '..') || ! preg_match('#^[A-Za-z0-9_\-./ ]+$#', $value)) {
            return null;
        }

        return ltrim($value, '/');
    }

    public static function url(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 2048 || ! preg_match('#^https?://[^\s"\'<>()\\\\]+$#i', $value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_URL) === false ? null : $value;
    }

    public static function bool(mixed $value): ?bool
    {
        return match (true) {
            $value === true, $value === 1, $value === '1', $value === 'true' => true,
            $value === false, $value === 0, $value === '0', $value === 'false' => false,
            default => null,
        };
    }

    /**
     * Space-separated CSS class names, for developers who target a block from their own
     * code (an animation script, a custom stylesheet).
     */
    public static function classes(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $names = preg_split('/\s+/', trim($value)) ?: [];
        $names = array_values(array_unique(array_filter(
            $names,
            fn (string $name): bool => (bool) preg_match('/^-?[A-Za-z_][A-Za-z0-9_-]{0,63}$/', $name),
        )));

        return $names === [] ? null : implode(' ', array_slice($names, 0, 12));
    }

    /**
     * Turn a sanitised style into what the wrappers print.
     *
     * The canvas splits a block in two — the outer `.fpb-block` carries the selection
     * outline and the bar, the inner `.fpb-block-body` the content — so margin and width
     * go on the outside (the outline hugs the element, the margin sits beyond it) and
     * everything else on the inside. The public page has one wrapper and takes both.
     *
     * @param  array<string, mixed>  $style
     * @return array{outer: string, inner: string, combined: string, class: ?string, background: ?array<string, mixed>, sized: bool}
     */
    public static function compile(array $style): array
    {
        $style = self::sanitize($style);

        $outer = [];
        $inner = [];

        self::spacing($style, 'margin', $outer);

        if (isset($style['width'])) {
            $outer[] = 'width:'.$style['width'].($style['width_unit'] ?? '%');
        }

        if (isset($style['max_width'])) {
            $outer[] = 'max-width:'.$style['max_width'].($style['max_width_unit'] ?? 'px');
        }

        match ($style['element_align'] ?? null) {
            'left' => array_push($outer, 'margin-right:auto', 'margin-left:0'),
            'center' => array_push($outer, 'margin-left:auto', 'margin-right:auto'),
            'right' => array_push($outer, 'margin-left:auto', 'margin-right:0'),
            default => null,
        };

        self::spacing($style, 'padding', $inner);

        if (isset($style['opacity'])) {
            $inner[] = 'opacity:'.($style['opacity'] / 100);
        }

        if (isset($style['text_align'])) {
            $inner[] = 'text-align:'.$style['text_align'];
            $inner[] = '--fpb-text-align:'.$style['text_align'];
        }

        if (isset($style['text_color'])) {
            $inner[] = 'color:'.$style['text_color'];
        }

        if (isset($style['font_size'])) {
            $inner[] = 'font-size:'.$style['font_size'].($style['font_size_unit'] ?? 'px');
        }

        if (isset($style['font_weight'])) {
            $inner[] = 'font-weight:'.$style['font_weight'];
        }

        if (isset($style['line_height'])) {
            $inner[] = 'line-height:'.$style['line_height'];
        }

        if (isset($style['letter_spacing'])) {
            $inner[] = 'letter-spacing:'.$style['letter_spacing'].'px';
        }

        if (isset($style['text_transform'])) {
            $inner[] = 'text-transform:'.$style['text_transform'];
        }

        $background = self::background($style, $inner);

        if (isset($style['border_width']) && $style['border_width'] > 0) {
            $inner[] = 'border:'.$style['border_width'].'px '.($style['border_style'] ?? 'solid').' '.($style['border_color'] ?? 'currentColor');
        }

        if (isset($style['border_radius'])) {
            $inner[] = 'border-radius:'.$style['border_radius'].'px';
        }

        if (isset($style['shadow'])) {
            $inner[] = 'box-shadow:'.match ($style['shadow']) {
                'sm' => '0 1px 3px rgba(15,23,42,.12)',
                'md' => '0 6px 16px rgba(15,23,42,.14)',
                'lg' => '0 14px 34px rgba(15,23,42,.18)',
                'xl' => '0 24px 60px rgba(15,23,42,.24)',
            };
        }

        $sized = isset($style['min_height']);

        if ($sized) {
            $inner[] = 'min-height:'.$style['min_height'].($style['min_height_unit'] ?? 'px');
        }

        return [
            'outer' => implode(';', $outer),
            'inner' => implode(';', $inner),
            'combined' => implode(';', [...$outer, ...$inner]),
            'class' => $style['css_class'] ?? null,
            'background' => $background,
            'sized' => $sized,
        ];
    }

    /**
     * A style split into what the Style tab edits and what the Layout tab edits.
     *
     * @param  array<string, mixed>  $style
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function split(array $style): array
    {
        $layout = array_intersect_key($style, array_flip(self::LAYOUT_KEYS));

        return [array_diff_key($style, $layout), $layout];
    }

    /**
     * Whether a sanitised style asks for anything at all.
     *
     * @param  array<string, mixed>  $style
     */
    public static function isEmpty(array $style): bool
    {
        return self::sanitize($style) === [];
    }

    /**
     * @param  array<string, mixed>  $style
     * @param  array<int, string>  $declarations
     */
    protected static function spacing(array $style, string $property, array &$declarations): void
    {
        $unit = $style["{$property}_unit"] ?? 'px';

        foreach (self::SIDES as $side) {
            if (isset($style["{$property}_{$side}"])) {
                $declarations[] = "{$property}-{$side}:".$style["{$property}_{$side}"].$unit;
            }
        }
    }

    /**
     * Background declarations for the wrapper, plus the layer a video or overlay needs.
     *
     * A colour, gradient or image is plain CSS on the wrapper. A video cannot be a CSS
     * background, and an overlay has to sit between the picture and the content, so both
     * come back as a description of an extra layer for the view to render.
     *
     * @param  array<string, mixed>  $style
     * @param  array<int, string>  $declarations
     * @return array<string, mixed>|null
     */
    protected static function background(array $style, array &$declarations): ?array
    {
        $type = $style['background_type'] ?? (isset($style['background_color']) ? 'color' : null);
        $layer = [];

        if ($type === 'color' && isset($style['background_color'])) {
            $declarations[] = 'background-color:'.$style['background_color'];
        }

        if ($type === 'gradient' && ($gradient = self::gradient($style['gradient_from'] ?? null, $style['gradient_to'] ?? null, $style['gradient_angle'] ?? 180, $style['gradient_type'] ?? 'linear'))) {
            $declarations[] = 'background-image:'.$gradient;
        }

        if ($type === 'image' && isset($style['background_image'])) {
            $url = MediaUrl::public($style['background_image']);

            if ($url !== null) {
                $declarations[] = 'background-image:url("'.self::cssString($url).'")';
                $declarations[] = 'background-size:'.($style['background_size'] ?? 'cover');
                $declarations[] = 'background-position:'.($style['background_position'] ?? 'center');
                $declarations[] = 'background-repeat:'.(($style['background_repeat'] ?? false) ? 'repeat' : 'no-repeat');

                if ($style['background_fixed'] ?? false) {
                    $declarations[] = 'background-attachment:fixed';
                }
            }

            if (isset($style['background_color'])) {
                $declarations[] = 'background-color:'.$style['background_color'];
            }
        }

        if ($type === 'video') {
            $video = self::video($style);

            if ($video !== null) {
                $layer['video'] = $video;
            }

            if (isset($style['background_color'])) {
                $declarations[] = 'background-color:'.$style['background_color'];
            }
        }

        if (in_array($type, ['image', 'video'], true) && isset($style['overlay_color'])) {
            $layer['overlay'] = 'background:'.$style['overlay_color'].';opacity:'.(($style['overlay_opacity'] ?? 50) / 100);
        }

        if ($layer === []) {
            return null;
        }

        $declarations[] = 'position:relative';
        $declarations[] = 'isolation:isolate';

        return $layer;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>|null
     */
    protected static function video(array $style): ?array
    {
        $start = (int) ($style['video_start'] ?? 0);
        $end = isset($style['video_end']) && $style['video_end'] > $start ? (int) $style['video_end'] : null;
        $loop = ! ($style['video_once'] ?? false);
        $rate = (float) ($style['video_rate'] ?? 1);

        if (isset($style['background_video_url'])) {
            $embed = EmbedUrl::resolve($style['background_video_url']);

            if ($embed !== null && in_array($embed['provider'], ['youtube', 'vimeo'], true)) {
                return [
                    'kind' => 'iframe',
                    'src' => EmbedUrl::src($embed, [
                        'start' => $start,
                        'end' => $end,
                        'autoplay' => true,
                        'mute' => true,
                        'loop' => $loop,
                        'controls' => false,
                        'background' => true,
                    ]),
                ];
            }

            if ($embed === null && preg_match('#\.(mp4|webm|ogg|mov)(\?|$)#i', $style['background_video_url'])) {
                return ['kind' => 'file', 'src' => $style['background_video_url'], 'start' => $start, 'end' => $end, 'loop' => $loop, 'rate' => $rate];
            }
        }

        if (isset($style['background_video'])) {
            $src = MediaUrl::public($style['background_video']);

            if ($src !== null) {
                return ['kind' => 'file', 'src' => $src, 'start' => $start, 'end' => $end, 'loop' => $loop, 'rate' => $rate];
            }
        }

        return null;
    }

    /**
     * A CSS gradient between two checked colours, or null when either is missing.
     */
    public static function gradient(mixed $from, mixed $to, mixed $angle = 180, string $type = 'linear'): ?string
    {
        $from = self::color($from);
        $to = self::color($to);

        if ($from === null || $to === null) {
            return null;
        }

        if ($type === 'radial') {
            return "radial-gradient(circle, {$from}, {$to})";
        }

        $angle = self::number($angle, 0, 360) ?? 180;

        return "linear-gradient({$angle}deg, {$from}, {$to})";
    }

    /**
     * Escape a URL for a double-quoted CSS string.
     */
    protected static function cssString(string $value): string
    {
        return str_replace(['\\', '"', "\n", "\r", "\f"], ['\\\\', '\\"', '', '', ''], $value);
    }
}
