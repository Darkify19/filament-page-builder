<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\InlineEditable;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use CarlJanzell\FilamentPageBuilder\Editable;
use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

class ImageBlock implements InlineEditable, PageBlock
{
    /**
     * @return array<string, Editable>
     */
    public static function editables(): array
    {
        return [
            'alt' => Editable::text()->placeholder('Describe the image'),
        ];
    }

    public static function type(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return 'Image';
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-photo';
    }

    public static function category(): string
    {
        return 'content';
    }

    public static function description(): string
    {
        return 'A photograph, optionally with a gradient laid over it.';
    }

    public static function view(): string
    {
        return 'page-builder::components.image';
    }

    /**
     * @return array<int, string>
     */
    public static function fileFields(): array
    {
        return ['src'];
    }

    public static function isVisible(): bool
    {
        return true;
    }

    /**
     * The overlay's inline style, or null when the image has none.
     *
     * @param  array<string, mixed>  $data
     */
    public static function overlayStyle(array $data): ?string
    {
        if (! ($data['overlay'] ?? false)) {
            return null;
        }

        $gradient = BlockStyle::gradient(
            $data['gradient_from'] ?? null,
            $data['gradient_to'] ?? null,
            $data['gradient_angle'] ?? 180,
            ($data['gradient_type'] ?? 'linear') === 'radial' ? 'radial' : 'linear',
        );

        if ($gradient === null) {
            return null;
        }

        $opacity = BlockStyle::number($data['overlay_opacity'] ?? 100, 0, 100) ?? 100;

        return 'background:'.$gradient.';opacity:'.($opacity / 100);
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            FileUpload::make('src')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('pages'),
            TextInput::make('alt')->label('Alt text')->maxLength(255),
            Section::make('Gradient overlay')
                ->compact()
                ->collapsible()
                ->schema([
                    Toggle::make('overlay')
                        ->label('Lay a gradient over the image')
                        ->live(),
                    Grid::make(2)
                        ->visible(fn (Get $get): bool => (bool) $get('overlay'))
                        ->schema([
                            ColorPicker::make('gradient_from')->label('From')->rgba()->default('rgba(0, 0, 0, 0)')->live(debounce: 400),
                            ColorPicker::make('gradient_to')->label('To')->rgba()->default('rgba(0, 0, 0, 0.7)')->live(debounce: 400),
                            Select::make('gradient_type')
                                ->label('Type')
                                ->options(['linear' => 'Linear', 'radial' => 'Radial'])
                                ->default('linear')
                                ->selectablePlaceholder(false)
                                ->live(),
                            TextInput::make('gradient_angle')
                                ->label('Direction')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(360)
                                ->suffix('°')
                                ->default(180)
                                ->live(onBlur: true),
                            TextInput::make('overlay_opacity')
                                ->label('Strength')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->default(100)
                                ->live(onBlur: true)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
