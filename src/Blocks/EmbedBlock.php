<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Support\SafeUrl;
use Closure;
use Filament\Forms\Components\TextInput;

/**
 * An allowlisted iframe (video, map). Hosts the application has not named never render.
 */
class EmbedBlock implements PageBlock
{
    /**
     * @var array<int, string>
     */
    public const HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'player.vimeo.com',
        'vimeo.com',
        'www.google.com',
        'maps.google.com',
    ];

    public static function type(): string
    {
        return 'embed';
    }

    public static function label(): string
    {
        return 'Embed';
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-play-circle';
    }

    public static function category(): string
    {
        return 'content';
    }

    public static function description(): string
    {
        return 'A video or map from an allowlisted host.';
    }

    public static function view(): string
    {
        return 'page-builder::components.embed';
    }

    /**
     * @return array<int, string>
     */
    public static function fileFields(): array
    {
        return [];
    }

    public static function isVisible(): bool
    {
        return true;
    }

    public static function allows(?string $url): bool
    {
        if (! SafeUrl::allows($url) || ! is_string($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach (self::HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            TextInput::make('url')
                ->label('Embed URL')
                ->maxLength(2048)
                ->rule(function (): Closure {
                    return function (string $attribute, mixed $value, Closure $fail): void {
                        if (! filled($value)) {
                            return;
                        }

                        if (! is_string($value) || ! self::allows($value)) {
                            $fail('Use a YouTube, Vimeo or Google Maps URL.');
                        }
                    };
                }),
        ];
    }
}
