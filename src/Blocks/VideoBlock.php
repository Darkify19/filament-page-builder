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

    public static function type(): string
    {
        return 'video';
    }

    public static function label(): string
    {
        return 'Video';
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
        return 'An uploaded video with start time, autoplay and loop.';
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
                ->label('Video file')
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                ->maxSize(51200)
                ->disk('public')
                ->directory('pages'),
            TextInput::make('url')
                ->label('…or a link to a video file')
                ->placeholder('https://example.com/clip.mp4')
                ->helperText('For YouTube or Vimeo, use the Embed block.')
                ->maxLength(2048)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && BlockStyle::url($value) === null) {
                        $fail('Enter an http(s) link to an .mp4 or .webm file.');
                    }
                }),
            FileUpload::make('poster')
                ->label('Cover image')
                ->image()
                ->disk('public')
                ->directory('pages'),
            Grid::make(2)->schema([
                TextInput::make('start')
                    ->label('Start at')
                    ->placeholder('0:00')
                    ->helperText('Seconds or m:ss')
                    ->rule(fn (): Closure => self::timeRule()),
                TextInput::make('end')
                    ->label('Stop at')
                    ->placeholder('End')
                    ->rule(fn (): Closure => self::timeRule()),
            ]),
            Grid::make(2)->schema([
                Toggle::make('autoplay')->label('Autoplay')->helperText('Plays muted'),
                Toggle::make('loop')->label('Loop'),
                Toggle::make('muted')->label('Muted'),
                Toggle::make('controls')->label('Show controls')->default(true),
            ]),
            Select::make('ratio')
                ->label('Shape')
                ->options(self::RATIOS)
                ->default(''),
        ];
    }

    public static function timeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && EmbedUrl::seconds($value) === null) {
                $fail('Use seconds (90) or minutes and seconds (1:30).');
            }
        };
    }
}
