<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

/**
 * A video file the site hosts itself, with a start time and playback options.
 *
 * YouTube and Vimeo belong in the Embed block, which frames the provider's own player.
 */
class VideoBlock implements PageBlock
{
    /**
     * @var array<string, string>
     */
    public const RATIOS = [
        '' => 'Natural',
        '16-9' => '16:9',
        '4-3' => '4:3',
        '1-1' => '1:1',
        '9-16' => '9:16',
        '21-9' => '21:9',
    ];

    /**
     * RATIOS, translated. As with the embed block, only "Natural" is a word; the rest are
     * numbers that mean the same thing in every locale.
     *
     * @return array<string, string>
     */
    public static function ratios(): array
    {
        return [
            '' => __('page-builder::blocks.video.ratio_natural'),
            '16-9' => '16:9',
            '4-3' => '4:3',
            '1-1' => '1:1',
            '9-16' => '9:16',
            '21-9' => '21:9',
        ];
    }

    public static function type(): string
    {
        return 'video';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.video.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-film';
    }

    public static function category(): string
    {
        return 'media';
    }

    public static function description(): string
    {
        return __('page-builder::blocks.video.description');
    }

    public static function view(): string
    {
        return 'page-builder::components.video';
    }

    /**
     * @return array<int, string>
     */
    public static function fileFields(): array
    {
        return ['src', 'poster'];
    }

    public static function isVisible(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return ['controls' => true];
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            FileUpload::make('src')
                ->label(__('page-builder::blocks.video.src'))
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                ->maxSize(51200)
                ->disk('public')
                ->directory('pages'),
            TextInput::make('url')
                ->label(__('page-builder::blocks.video.url'))
                ->placeholder(__('page-builder::blocks.video.url_placeholder'))
                ->helperText(__('page-builder::blocks.video.url_hint'))
                ->maxLength(2048)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && BlockStyle::url($value) === null) {
                        $fail(__('page-builder::blocks.video.url_invalid'));
                    }
                }),
            FileUpload::make('poster')
                ->label(__('page-builder::blocks.video.poster'))
                ->image()
                ->disk('public')
                ->directory('pages'),
            Grid::make(2)->schema([
                TextInput::make('start')
                    ->label(__('page-builder::blocks.common.start_at'))
                    ->placeholder('0:00')
                    ->helperText(__('page-builder::blocks.common.time_hint'))
                    ->rule(fn (): Closure => self::timeRule()),
                TextInput::make('end')
                    ->label(__('page-builder::blocks.common.stop_at'))
                    ->placeholder(__('page-builder::blocks.common.end_placeholder'))
                    ->rule(fn (): Closure => self::timeRule()),
            ]),
            Grid::make(2)->schema([
                Toggle::make('autoplay')->label(__('page-builder::blocks.common.autoplay'))->helperText(__('page-builder::blocks.common.autoplay_hint')),
                Toggle::make('loop')->label(__('page-builder::blocks.common.loop')),
                Toggle::make('muted')->label(__('page-builder::blocks.common.muted')),
                Toggle::make('controls')->label(__('page-builder::blocks.video.controls'))->default(true),
            ]),
            Select::make('ratio')
                ->label(__('page-builder::blocks.common.shape'))
                ->options(self::ratios())
                ->default(''),
        ];
    }

    public static function timeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && EmbedUrl::seconds($value) === null) {
                $fail(__('page-builder::blocks.common.time_invalid'));
            }
        };
    }
}
