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

    /**
     * RATIOS, translated.
     *
     * Only the three keys that name a shape are localised. The others are aspect ratios:
     * `4:3` is the same number in every language, and a translator turning it into a word
     * would change what the aspect-ratio attribute means.
     *
     * @return array<string, string>
     */
    public static function ratios(): array
    {
        return [
            '16-9' => __('page-builder::blocks.embed.ratio_video'),
            '4-3' => '4:3',
            '1-1' => __('page-builder::blocks.embed.ratio_square'),
            '3-4' => __('page-builder::blocks.embed.ratio_form'),
            '9-16' => __('page-builder::blocks.embed.ratio_vertical'),
            '21-9' => __('page-builder::blocks.embed.ratio_cinema'),
        ];
    }

    public static function type(): string
    {
        return 'embed';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.embed.label');
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
        return __('page-builder::blocks.embed.description');
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
                ->label(__('page-builder::blocks.embed.url'))
                ->rows(2)
                ->placeholder(__('page-builder::blocks.embed.url_placeholder'))
                ->helperText(__('page-builder::blocks.embed.url_hint'))
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
                        ->label(__('page-builder::blocks.common.start_at'))
                        ->placeholder('0:00')
                        ->helperText(__('page-builder::blocks.common.time_hint'))
                        ->rule(fn (): Closure => VideoBlock::timeRule()),
                    TextInput::make('end')
                        ->label(__('page-builder::blocks.common.stop_at'))
                        ->placeholder(__('page-builder::blocks.common.end_placeholder'))
                        ->rule(fn (): Closure => VideoBlock::timeRule()),
                    Toggle::make('autoplay')->label(__('page-builder::blocks.common.autoplay'))->helperText(__('page-builder::blocks.common.autoplay_hint')),
                    Toggle::make('loop')->label(__('page-builder::blocks.common.loop')),
                    Toggle::make('mute')->label(__('page-builder::blocks.common.muted')),
                    Toggle::make('hide_controls')->label(__('page-builder::blocks.embed.hide_controls')),
                ]),
            Grid::make(2)->schema([
                Select::make('ratio')
                    ->label(__('page-builder::blocks.common.shape'))
                    ->options(self::ratios())
                    ->placeholder(__('page-builder::blocks.embed.ratio_video')),
                TextInput::make('height')
                    ->label(__('page-builder::blocks.embed.fixed_height'))
                    ->numeric()
                    ->minValue(80)
                    ->maxValue(2000)
                    ->suffix('px')
                    ->placeholder(__('page-builder::blocks.common.auto')),
            ]),
            TextInput::make('title')
                ->label(__('page-builder::blocks.embed.title'))
                ->maxLength(255)
                ->placeholder(__('page-builder::blocks.embed.title_placeholder')),
        ];
    }
}
