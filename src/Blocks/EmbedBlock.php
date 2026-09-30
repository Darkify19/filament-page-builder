<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

/**
 * An allowlisted iframe: a video, a map, a form, a slide deck.
 *
 * The editor pastes whatever the provider gave them — the page address, a share link or
 * the whole embed snippet — and `EmbedUrl` turns it into the one address that provider
 * lets another site frame. Hosts outside the allowlist never render.
 */
class EmbedBlock implements PageBlock
{
    /**
     * Kept for callers that read it; the list itself now lives on EmbedUrl.
     *
     * @var array<int, string>
     */
    public const HOSTS = EmbedUrl::HOSTS;

    /**
     * @var array<string, string>
     */
    public const RATIOS = [
        '16-9' => '16:9 (video)',
        '4-3' => '4:3',
        '1-1' => 'Square',
        '3-4' => '3:4 (form, document)',
        '9-16' => '9:16 (vertical video)',
        '21-9' => '21:9 (cinema)',
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
        return 'media';
    }

    public static function description(): string
    {
        return 'YouTube, Vimeo, Maps, Google Forms and Slides, Spotify, Canva…';
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
        return EmbedUrl::resolve($url) !== null;
    }

    /**
     * The iframe address for a block's data, or null when nothing can be framed.
     *
     * @param  array<string, mixed>  $data
     */
    public static function src(array $data, bool $editing = false): ?string
    {
        $embed = EmbedUrl::resolve($data['url'] ?? null);

        if ($embed === null) {
            return null;
        }

        return EmbedUrl::src($embed, [
            'start' => EmbedUrl::seconds($data['start'] ?? null),
            'end' => EmbedUrl::seconds($data['end'] ?? null),
            // Autoplay on the canvas would restart the video on every re-render.
            'autoplay' => ! $editing && (bool) ($data['autoplay'] ?? false),
            'mute' => (bool) ($data['mute'] ?? false) || (! $editing && (bool) ($data['autoplay'] ?? false)),
            'loop' => (bool) ($data['loop'] ?? false),
            'controls' => ! (bool) ($data['hide_controls'] ?? false),
        ]);
    }

    public static function isPlayable(mixed $url): bool
    {
        return in_array(EmbedUrl::resolve($url)['provider'] ?? null, ['youtube', 'vimeo'], true);
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Textarea::make('url')
                ->label('Link or embed code')
                ->rows(2)
                ->placeholder('https://www.youtube.com/watch?v=…')
                ->helperText('Paste the page address, a share link, or the whole embed code.')
                ->maxLength(4096)
                ->live(onBlur: true)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ($problem = EmbedUrl::problem($value)) !== null) {
                        $fail($problem);
                    }
                }),
            Grid::make(2)
                ->visible(fn (Get $get): bool => self::isPlayable($get('url')))
                ->schema([
                    TextInput::make('start')
                        ->label('Start at')
                        ->placeholder('0:00')
                        ->helperText('Seconds or m:ss')
                        ->rule(fn (): Closure => VideoBlock::timeRule()),
                    TextInput::make('end')
                        ->label('Stop at')
                        ->placeholder('End')
                        ->rule(fn (): Closure => VideoBlock::timeRule()),
                    Toggle::make('autoplay')->label('Autoplay')->helperText('Plays muted'),
                    Toggle::make('loop')->label('Loop'),
                    Toggle::make('mute')->label('Muted'),
                    Toggle::make('hide_controls')->label('Hide controls'),
                ]),
            Grid::make(2)->schema([
                Select::make('ratio')
                    ->label('Shape')
                    ->options(self::RATIOS)
                    ->placeholder('16:9 (video)'),
                TextInput::make('height')
                    ->label('Fixed height')
                    ->numeric()
                    ->minValue(80)
                    ->maxValue(2000)
                    ->suffix('px')
                    ->placeholder('Auto'),
            ]),
            TextInput::make('title')
                ->label('Title for screen readers')
                ->maxLength(255)
                ->placeholder('Embedded content'),
        ];
    }
}
