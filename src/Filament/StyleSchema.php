<?php

namespace CarlJanzell\FilamentPageBuilder\Filament;

use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

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
        return self::tab(Section::make(__('page-builder::style.tabs.typography'))->icon('heroicon-m-language'))->schema([
            ToggleButtons::make('text_align')
                ->label(__('page-builder::style.alignment.label'))
                ->options([
                    'left' => __('page-builder::style.common.left'),
                    'center' => __('page-builder::style.common.centre'),
                    'right' => __('page-builder::style.common.right'),
                    'justify' => __('page-builder::style.common.justify'),
                ])
                ->icons([
                    'left' => 'heroicon-m-bars-3-bottom-left',
                    'center' => 'heroicon-m-bars-3',
                    'right' => 'heroicon-m-bars-3-bottom-right',
                    'justify' => 'heroicon-m-bars-4',
                ])
                ->tooltips([
                    'left' => __('page-builder::style.alignment.align_left'),
                    'center' => __('page-builder::style.common.centre'),
                    'right' => __('page-builder::style.alignment.align_right'),
                    'justify' => __('page-builder::style.common.justify'),
                ])
                ->hiddenButtonLabels()
                ->inline()
                ->grouped()
                ->live(),
            ColorPicker::make('text_color')
                ->label(__('page-builder::style.common.colour'))
                ->live(debounce: 500),
            Grid::make(2)->schema([
                TextInput::make('font_size')
                    ->label(__('page-builder::style.common.size'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(400)
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->live(onBlur: true),
                Select::make('font_size_unit')
                    ->label(__('page-builder::style.common.unit'))
                    ->options(array_combine(['px', 'rem', 'em', 'vw'], ['px', 'rem', 'em', 'vw']))
                    ->placeholder('px')
                    ->live(),
                Select::make('font_weight')
                    ->label(__('page-builder::style.weight.label'))
                    ->options([
                        '300' => __('page-builder::style.weight.light'),
                        '400' => __('page-builder::style.weight.regular'),
                        '500' => __('page-builder::style.weight.medium'),
                        '600' => __('page-builder::style.weight.semibold'),
                        '700' => __('page-builder::style.weight.bold'),
                        '800' => __('page-builder::style.weight.extra_bold'),
                        '900' => __('page-builder::style.weight.black'),
                    ])
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->live(),
                TextInput::make('line_height')
                    ->label(__('page-builder::style.common.line_height'))
                    ->numeric()
                    ->minValue(0.5)
                    ->maxValue(5)
                    ->step(0.05)
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->live(onBlur: true),
                TextInput::make('letter_spacing')
                    ->label(__('page-builder::style.common.letter_spacing'))
                    ->numeric()
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('text_transform')
                    ->label(__('page-builder::style.case.label'))
                    ->options([
                        'uppercase' => 'UPPERCASE',
                        'lowercase' => 'lowercase',
                        'capitalize' => __('page-builder::style.case.capitalise'),
                        'none' => __('page-builder::style.case.as_typed'),
                    ])
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->live(),
            ]),
        ]);
    }

    protected static function background(): Section
    {
        $is = fn (string ...$types): Closure => fn (Get $get): bool => in_array($get('background_type'), $types, true);

        return self::tab(Section::make(__('page-builder::style.tabs.background'))->icon('heroicon-m-swatch'))->schema([
            ToggleButtons::make('background_type')
                ->hiddenLabel()
                ->options([
                    'none' => __('page-builder::style.background.type_none'),
                    'color' => __('page-builder::style.background.type_colour'),
                    'gradient' => __('page-builder::style.background.type_gradient'),
                    'image' => __('page-builder::style.background.type_image'),
                    'video' => __('page-builder::style.background.type_video'),
                ])
                ->inline()
                ->live(),
            ColorPicker::make('background_color')
                ->label(fn (Get $get): string => $get('background_type') === 'color'
                    ? __('page-builder::style.common.colour')
                    : __('page-builder::style.background.fallback_colour'))
                ->rgba()
                ->visible($is('color', 'image', 'video'))
                ->live(debounce: 500),
            Grid::make(2)
                ->visible($is('gradient'))
                ->schema([
                    ColorPicker::make('gradient_from')->label(__('page-builder::style.background.from'))->rgba()->live(debounce: 500),
                    ColorPicker::make('gradient_to')->label(__('page-builder::style.background.to'))->rgba()->live(debounce: 500),
                    Select::make('gradient_type')
                        ->label(__('page-builder::style.background.type'))
                        ->options([
                            'linear' => __('page-builder::style.background.linear'),
                            'radial' => __('page-builder::style.background.radial'),
                        ])
                        ->placeholder(__('page-builder::style.background.linear'))
                        ->live(),
                    TextInput::make('gradient_angle')
                        ->label(__('page-builder::style.background.direction'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(360)
                        ->suffix('°')
                        ->placeholder('180')
                        ->live(onBlur: true),
                ]),
            FileUpload::make('background_image')
                ->label(__('page-builder::style.background.image'))
                ->image()
                ->disk('public')
                ->directory('pages')
                ->visible($is('image')),
            Grid::make(2)
                ->visible($is('image'))
                ->schema([
                    Select::make('background_size')
                        ->label(__('page-builder::style.background.fit'))
                        ->options([
                            'cover' => __('page-builder::style.background.fit_fill'),
                            'contain' => __('page-builder::style.background.fit_contain'),
                            'auto' => __('page-builder::style.background.fit_auto'),
                        ])
                        ->placeholder(__('page-builder::style.background.fit_fill'))
                        ->live(),
                    Select::make('background_position')
                        ->label(__('page-builder::style.background.focus'))
                        ->options([
                            'center' => __('page-builder::style.background.focus_centre'),
                            'top' => __('page-builder::style.background.focus_top'),
                            'bottom' => __('page-builder::style.background.focus_bottom'),
                            'left' => __('page-builder::style.background.focus_left'),
                            'right' => __('page-builder::style.background.focus_right'),
                            'top left' => __('page-builder::style.background.focus_top_left'),
                            'top right' => __('page-builder::style.background.focus_top_right'),
                            'bottom left' => __('page-builder::style.background.focus_bottom_left'),
                            'bottom right' => __('page-builder::style.background.focus_bottom_right'),
                        ])
                        ->placeholder(__('page-builder::style.background.focus_centre'))
                        ->live(),
                    Toggle::make('background_fixed')->label(__('page-builder::style.background.parallax'))->live(),
                    Toggle::make('background_repeat')->label(__('page-builder::style.background.tile'))->live(),
                ]),
            FileUpload::make('background_video')
                ->label(__('page-builder::style.background.video_file'))
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                ->maxSize(51200)
                ->disk('public')
                ->directory('pages')
                ->visible($is('video')),
            TextInput::make('background_video_url')
                ->label(__('page-builder::style.background.video_link'))
                ->placeholder(__('page-builder::style.background.video_link_placeholder'))
                ->visible($is('video'))
                ->live(onBlur: true)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && BlockStyle::url($value) === null) {
                        $fail(__('page-builder::style.background.video_link_invalid'));
                    }
                }),
            Grid::make(2)
                ->visible($is('video'))
                ->schema([
                    TextInput::make('video_start')
                        ->label(__('page-builder::style.background.start_at'))
                        ->placeholder('0:00')
                        ->helperText(__('page-builder::style.background.time_hint'))
                        ->formatStateUsing(fn (mixed $state): ?string => self::timecode($state))
                        ->dehydrateStateUsing(fn (mixed $state): ?int => EmbedUrl::seconds($state))
                        ->live(onBlur: true),
                    TextInput::make('video_end')
                        ->label(__('page-builder::style.background.stop_at'))
                        ->placeholder(__('page-builder::style.background.end_placeholder'))
                        ->formatStateUsing(fn (mixed $state): ?string => self::timecode($state))
                        ->dehydrateStateUsing(fn (mixed $state): ?int => EmbedUrl::seconds($state))
                        ->live(onBlur: true),
                    Toggle::make('video_once')->label(__('page-builder::style.background.play_once'))->helperText(__('page-builder::style.background.play_once_hint'))->live(),
                    Select::make('video_rate')
                        ->label(__('page-builder::style.background.speed'))
                        ->options([
                            '0.5' => '0.5×',
                            '0.75' => '0.75×',
                            '1' => __('page-builder::style.background.speed_normal'),
                            '1.25' => '1.25×',
                            '1.5' => '1.5×',
                            '2' => '2×',
                        ])
                        ->placeholder(__('page-builder::style.background.speed_normal'))
                        ->live(),
                ]),
            Fieldset::make(__('page-builder::style.background.overlay'))
                ->visible($is('image', 'video'))
                ->columns(2)
                ->schema([
                    ColorPicker::make('overlay_color')->label(__('page-builder::style.common.colour'))->rgba()->live(debounce: 500),
                    TextInput::make('overlay_opacity')
                        ->label(__('page-builder::style.background.strength'))
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
        return self::tab(Section::make(__('page-builder::style.tabs.border'))->icon('heroicon-m-stop')->collapsed())->schema([
            Grid::make(2)->schema([
                TextInput::make('border_width')
                    ->label(__('page-builder::style.border.border'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(50)
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('border_style')
                    ->label(__('page-builder::style.border.line'))
                    ->options([
                        'solid' => __('page-builder::style.border.solid'),
                        'dashed' => __('page-builder::style.border.dashed'),
                        'dotted' => __('page-builder::style.border.dotted'),
                        'double' => __('page-builder::style.border.double'),
                    ])
                    ->placeholder(__('page-builder::style.border.solid'))
                    ->live(),
                ColorPicker::make('border_color')->label(__('page-builder::style.border.border_colour'))->rgba()->live(debounce: 500),
                TextInput::make('border_radius')
                    ->label(__('page-builder::style.border.rounded_corners'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(500)
                    ->suffix('px')
                    ->placeholder('0')
                    ->live(onBlur: true),
                Select::make('shadow')
                    ->label(__('page-builder::style.border.shadow'))
                    ->options([
                        'sm' => __('page-builder::style.border.shadow_subtle'),
                        'md' => __('page-builder::style.border.shadow_medium'),
                        'lg' => __('page-builder::style.border.shadow_large'),
                        'xl' => __('page-builder::style.border.shadow_dramatic'),
                    ])
                    ->placeholder(__('page-builder::style.common.none'))
                    ->live(),
                TextInput::make('opacity')
                    ->label(__('page-builder::style.border.opacity'))
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
        return self::tab(Section::make(__('page-builder::style.tabs.spacing'))->icon('heroicon-m-arrows-pointing-out'))->schema([
            self::sides('padding', __('page-builder::style.spacing.padding'), 0),
            self::sides('margin', __('page-builder::style.spacing.margin'), -2000),
        ]);
    }

    protected static function sides(string $property, string $label, int $min): Fieldset
    {
        $inputs = array_map(
            fn (string $side): TextInput => TextInput::make("{$property}_{$side}")
                ->label(self::sideLabel($side))
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
                    ->label(__('page-builder::style.common.unit'))
                    ->inlineLabel()
                    ->options(array_combine(BlockStyle::SPACING_UNITS, BlockStyle::SPACING_UNITS))
                    ->placeholder('px')
                    ->columnSpanFull()
                    ->live(),
            ]);
    }

    protected static function size(): Section
    {
        return self::tab(Section::make(__('page-builder::style.tabs.size'))->icon('heroicon-m-arrows-right-left'))->schema([
            Grid::make(2)->schema([
                TextInput::make('width')
                    ->label(__('page-builder::style.common.width'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4000)
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->helperText(__('page-builder::style.size.width_hint'))
                    ->live(onBlur: true),
                Select::make('width_unit')
                    ->label(__('page-builder::style.common.unit'))
                    ->options(['%' => '%', 'px' => 'px', 'rem' => 'rem', 'vw' => 'vw'])
                    ->placeholder('%')
                    ->live(),
                TextInput::make('max_width')
                    ->label(__('page-builder::style.common.max_width'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4000)
                    ->placeholder(__('page-builder::style.common.none'))
                    ->live(onBlur: true),
                Select::make('max_width_unit')
                    ->label(__('page-builder::style.common.unit'))
                    ->options(['px' => 'px', 'rem' => 'rem', '%' => '%', 'vw' => 'vw'])
                    ->placeholder('px')
                    ->live(),
                TextInput::make('min_height')
                    ->label(__('page-builder::style.common.min_height'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(4000)
                    ->placeholder(__('page-builder::style.common.auto'))
                    ->helperText(__('page-builder::style.size.min_height_hint'))
                    ->live(onBlur: true),
                Select::make('min_height_unit')
                    ->label(__('page-builder::style.common.unit'))
                    ->options(['px' => 'px', 'rem' => 'rem', 'vh' => 'vh'])
                    ->placeholder('px')
                    ->live(),
            ]),
            ToggleButtons::make('element_align')
                ->label(__('page-builder::style.size.position'))
                ->options([
                    'left' => __('page-builder::style.common.left'),
                    'center' => __('page-builder::style.common.centre'),
                    'right' => __('page-builder::style.common.right'),
                ])
                ->inline()
                ->grouped()
                ->live(),
        ]);
    }

    protected static function developer(): Section
    {
        return self::tab(Section::make(__('page-builder::style.tabs.developer'))->icon('heroicon-m-code-bracket')->collapsed())->schema([
            TextInput::make('css_class')
                ->label(__('page-builder::style.developer.css_classes'))
                ->placeholder(__('page-builder::style.developer.css_classes_placeholder'))
                ->helperText(__('page-builder::style.developer.css_classes_hint'))
                ->live(onBlur: true),
        ]);
    }

    /**
     * The label on one edge of a spacing fieldset: "Top", "Right", and so on.
     *
     * These were `ucfirst($side)` on the raw side name, which is English baked into the
     * schema and would have kept saying "Top" under every locale but English.
     */
    protected static function sideLabel(string $side): string
    {
        return match ($side) {
            'top' => __('page-builder::style.common.top'),
            'bottom' => __('page-builder::style.common.bottom'),
            'left' => __('page-builder::style.common.left'),
            'right' => __('page-builder::style.common.right'),
            default => ucfirst($side),
        };
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
