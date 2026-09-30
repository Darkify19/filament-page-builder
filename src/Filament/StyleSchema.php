<?php

namespace CarlJanzell\FilamentPageBuilder\Filament;

use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;

/**
 * The inspector's Style and Layout tabs: every field of the custom style layer.
 *
 * Two schemas, one per tab, over the same `style` key on the block: the Style tab holds
 * what a block looks like, the Layout tab where it sits and how big it is. The field
 * names are the keys `BlockStyle` stores (the Layout tab owns `BlockStyle::LAYOUT_KEYS`),
 * and that class, not these forms, decides what is acceptable.
 */
class StyleSchema
{
    /**
     * @return array<int, mixed>
     */
    public static function style(): array
    {
        return [
            self::typography(),
            self::background(),
            self::border(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function layout(): array
    {
        return [
            self::spacing(),
            self::size(),
            self::developer(),
        ];
    }

    protected static function tab(Section $section): Section
    {
        return $section
            ->compact()
            ->collapsible();
    }

    protected static function typography(): Section
    {
        return self::tab(Section::make('Text')->icon('heroicon-m-language'))->schema([
            ToggleButtons::make('text_align')
                ->label('Alignment')
                ->options([
                    'left' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right',
                    'justify' => 'Justify',
                ])
                ->icons([
                    'left' => 'heroicon-m-bars-3-bottom-left',
                    'center' => 'heroicon-m-bars-3',
                    'right' => 'heroicon-m-bars-3-bottom-right',
                    'justify' => 'heroicon-m-bars-4',
                ])
                ->hiddenButtonLabels()
                ->inline()
                ->grouped()
                ->live(),
            ColorPicker::make('text_color')
                ->label('Colour')
                ->live(debounce: 500),
            Grid::make(2)->schema([
                TextInput::make('font_size')
                    ->label('Size')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(400)
                    ->placeholder('Auto')
                    ->live(onBlur: true),
                Select::make('font_size_unit')
                    ->label('Unit')
                    ->options(array_combine(['px', 'rem', 'em', 'vw'], ['px', 'rem', 'em', 'vw']))
                    ->placeholder('px')
                    ->live(),
                Select::make('font_weight')
                    ->label('Weight')
                    ->options([
                        '300' => 'Light',
                        '400' => 'Regular',
                        '500' => 'Medium',
                        '600' => 'Semibold',
                        '700' => 'Bold',
                        '800' => 'Extra bold',
                        '900' => 'Black',
                    ])
                    ->placeholder('Auto')
                    ->live(),
                TextInput::make('line_height')
                    ->label('Line height')
                    ->numeric()
                    ->minValue(0.5)
                    ->maxValue(5)
                    ->step(0.05)
                    ->placeholder('Auto')
                    ->live(onBlur: true),
                TextInput::make('letter_spacing')
                    ->label('Letter spacing')
                    ->numeric()
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('text_transform')
                    ->label('Case')
                    ->options([
                        'uppercase' => 'UPPERCASE',
                        'lowercase' => 'lowercase',
                        'capitalize' => 'Capitalise',
                        'none' => 'As typed',
                    ])
                    ->placeholder('Auto')
                    ->live(),
            ]),
        ]);
    }

    protected static function background(): Section
    {
        $is = fn (string ...$types): Closure => fn (Get $get): bool => in_array($get('background_type'), $types, true);

        return self::tab(Section::make('Background')->icon('heroicon-m-swatch'))->schema([
            ToggleButtons::make('background_type')
                ->hiddenLabel()
                ->options([
                    'none' => 'None',
                    'color' => 'Colour',
                    'gradient' => 'Gradient',
                    'image' => 'Image',
                    'video' => 'Video',
                ])
                ->inline()
                ->live(),
            ColorPicker::make('background_color')
                ->label(fn (Get $get): string => $get('background_type') === 'color' ? 'Colour' : 'Fallback colour')
                ->rgba()
                ->visible($is('color', 'image', 'video'))
                ->live(debounce: 500),
            Grid::make(2)
                ->visible($is('gradient'))
                ->schema([
                    ColorPicker::make('gradient_from')->label('From')->rgba()->live(debounce: 500),
                    ColorPicker::make('gradient_to')->label('To')->rgba()->live(debounce: 500),
                    Select::make('gradient_type')
                        ->label('Type')
                        ->options(['linear' => 'Linear', 'radial' => 'Radial'])
                        ->placeholder('Linear')
                        ->live(),
                    TextInput::make('gradient_angle')
                        ->label('Direction')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(360)
                        ->suffix('°')
                        ->placeholder('180')
                        ->live(onBlur: true),
                ]),
            FileUpload::make('background_image')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('pages')
                ->visible($is('image')),
            Grid::make(2)
                ->visible($is('image'))
                ->schema([
                    Select::make('background_size')
                        ->label('Fit')
                        ->options(['cover' => 'Fill', 'contain' => 'Fit inside', 'auto' => 'Actual size'])
                        ->placeholder('Fill')
                        ->live(),
                    Select::make('background_position')
                        ->label('Focus')
                        ->options(array_combine(
                            ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'],
                            ['Centre', 'Top', 'Bottom', 'Left', 'Right', 'Top left', 'Top right', 'Bottom left', 'Bottom right'],
                        ))
                        ->placeholder('Centre')
                        ->live(),
                    Toggle::make('background_fixed')->label('Parallax')->live(),
                    Toggle::make('background_repeat')->label('Tile')->live(),
                ]),
            FileUpload::make('background_video')
                ->label('Video file')
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                ->maxSize(51200)
                ->disk('public')
                ->directory('pages')
                ->visible($is('video')),
            TextInput::make('background_video_url')
                ->label('…or a video link')
                ->placeholder('YouTube, Vimeo or an .mp4 link')
                ->visible($is('video'))
                ->live(onBlur: true)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && BlockStyle::url($value) === null) {
                        $fail('Enter an http(s) link.');
                    }
                }),
            Grid::make(2)
                ->visible($is('video'))
                ->schema([
                    TextInput::make('video_start')
                        ->label('Start at')
                        ->placeholder('0:00')
                        ->helperText('Seconds or m:ss')
                        ->formatStateUsing(fn (mixed $state): ?string => self::timecode($state))
                        ->dehydrateStateUsing(fn (mixed $state): ?int => EmbedUrl::seconds($state))
                        ->live(onBlur: true),
                    TextInput::make('video_end')
                        ->label('Stop at')
                        ->placeholder('End')
                        ->formatStateUsing(fn (mixed $state): ?string => self::timecode($state))
                        ->dehydrateStateUsing(fn (mixed $state): ?int => EmbedUrl::seconds($state))
                        ->live(onBlur: true),
                    Toggle::make('video_once')->label('Play once')->helperText('Loops unless this is on')->live(),
                    Select::make('video_rate')
                        ->label('Speed')
                        ->options(['0.5' => '0.5×', '0.75' => '0.75×', '1' => 'Normal', '1.25' => '1.25×', '1.5' => '1.5×', '2' => '2×'])
                        ->placeholder('Normal')
                        ->live(),
                ]),
            Fieldset::make('Overlay')
                ->visible($is('image', 'video'))
                ->columns(2)
                ->schema([
                    ColorPicker::make('overlay_color')->label('Colour')->rgba()->live(debounce: 500),
                    TextInput::make('overlay_opacity')
                        ->label('Strength')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->placeholder('50')
                        ->live(onBlur: true),
                ]),
        ]);
    }

    protected static function border(): Section
    {
        return self::tab(Section::make('Border and shadow')->icon('heroicon-m-stop')->collapsed())->schema([
            Grid::make(2)->schema([
                TextInput::make('border_width')
                    ->label('Border')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(50)
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('border_style')
                    ->label('Line')
                    ->options(['solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted', 'double' => 'Double'])
                    ->placeholder('Solid')
                    ->live(),
                ColorPicker::make('border_color')->label('Border colour')->rgba()->live(debounce: 500),
                TextInput::make('border_radius')
                    ->label('Rounded corners')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(500)
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('shadow')
                    ->label('Shadow')
                    ->options(['sm' => 'Subtle', 'md' => 'Medium', 'lg' => 'Large', 'xl' => 'Dramatic'])
                    ->placeholder('None')
                    ->live(),
                TextInput::make('opacity')
                    ->label('Opacity')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->placeholder('100')
                    ->live(onBlur: true),
            ]),
        ]);
    }

    protected static function spacing(): Section
    {
        return self::tab(Section::make('Spacing')->icon('heroicon-m-arrows-pointing-out'))->schema([
            self::sides('padding', 'Padding — space inside', 0),
            self::sides('margin', 'Margin — space outside', -2000),
        ]);
    }

    protected static function sides(string $property, string $label, int $min): Fieldset
    {
        $inputs = array_map(
            fn (string $side): TextInput => TextInput::make("{$property}_{$side}")
                ->label(ucfirst($side))
                ->numeric()
                ->minValue($min)
                ->maxValue(2000)
                ->placeholder('–')
                ->live(onBlur: true),
            BlockStyle::SIDES,
        );

        return Fieldset::make($label)
            ->columns(4)
            ->schema([
                ...$inputs,
                Select::make("{$property}_unit")
                    ->label('Unit')
                    ->inlineLabel()
                    ->options(array_combine(BlockStyle::SPACING_UNITS, BlockStyle::SPACING_UNITS))
                    ->placeholder('px')
                    ->columnSpanFull()
                    ->live(),
            ]);
    }

    protected static function size(): Section
    {
        return self::tab(Section::make('Size and position')->icon('heroicon-m-arrows-right-left'))->schema([
            Grid::make(2)->schema([
                TextInput::make('width')
                    ->label('Width')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4000)
                    ->placeholder('Auto')
                    ->helperText('Or drag the right edge on the page.')
                    ->live(onBlur: true),
                Select::make('width_unit')
                    ->label('Unit')
                    ->options(['%' => '%', 'px' => 'px', 'rem' => 'rem', 'vw' => 'vw'])
                    ->placeholder('%')
                    ->live(),
                TextInput::make('max_width')
                    ->label('Max width')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4000)
                    ->placeholder('None')
                    ->live(onBlur: true),
                Select::make('max_width_unit')
                    ->label('Unit')
                    ->options(['px' => 'px', 'rem' => 'rem', '%' => '%', 'vw' => 'vw'])
                    ->placeholder('px')
                    ->live(),
                TextInput::make('min_height')
                    ->label('Min height')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(4000)
                    ->placeholder('Auto')
                    ->helperText('Or drag the bottom edge.')
                    ->live(onBlur: true),
                Select::make('min_height_unit')
                    ->label('Unit')
                    ->options(['px' => 'px', 'rem' => 'rem', 'vh' => 'vh'])
                    ->placeholder('px')
                    ->live(),
            ]),
            ToggleButtons::make('element_align')
                ->label('Position when narrower than its column')
                ->options(['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'])
                ->inline()
                ->grouped()
                ->live(),
        ]);
    }

    protected static function developer(): Section
    {
        return self::tab(Section::make('For developers')->icon('heroicon-m-code-bracket')->collapsed())->schema([
            TextInput::make('css_class')
                ->label('CSS classes')
                ->placeholder('hero-title fade-in')
                ->helperText('Target this block from Custom code or your own stylesheet.')
                ->live(onBlur: true),
        ]);
    }

    protected static function timecode(mixed $seconds): ?string
    {
        if (! is_numeric($seconds)) {
            return is_string($seconds) && $seconds !== '' ? $seconds : null;
        }

        $seconds = (int) $seconds;

        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
