<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\Container;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;

/**
 * A row of columns that other blocks drop into.
 *
 * This is the layout primitive: the page is still made of typed
 * blocks, but a section is how an editor composes them side by side. The package
 * ships it so every consuming app has columns without writing a container themselves;
 * registering another block with type `section` replaces it.
 */
class SectionBlock implements Container, PageBlock
{
    /**
     * @var array<string, string>
     */
    public const GAPS = [
        'none' => 'None',
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
        'xl' => 'Extra large',
    ];

    /**
     * GAPS, translated, plus the column alignment options that had no constant to hang on.
     *
     * @return array<string, string>
     */
    public static function gaps(): array
    {
        return [
            'none' => __('page-builder::blocks.common.none'),
            'sm' => __('page-builder::blocks.common.small'),
            'md' => __('page-builder::blocks.common.medium'),
            'lg' => __('page-builder::blocks.common.large'),
            'xl' => __('page-builder::blocks.common.extra_large'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function verticalAlignment(): array
    {
        return [
            'start' => __('page-builder::blocks.section.top'),
            'center' => __('page-builder::blocks.section.middle'),
            'end' => __('page-builder::blocks.section.bottom'),
            'stretch' => __('page-builder::blocks.section.same_height'),
        ];
    }

    public static function type(): string
    {
        return 'section';
    }

    public static function label(): string
    {
        return __('page-builder::blocks.section.label');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-view-columns';
    }

    public static function category(): string
    {
        return 'layout';
    }

    public static function view(): string
    {
        return 'page-builder::components.section';
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

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'columns' => 2,
            'ratio' => '1-1',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function slots(array $data): array
    {
        $count = max(1, min(4, (int) ($data['columns'] ?? 2)));

        return array_map(fn (int $i): string => 'col-'.$i, range(0, $count - 1));
    }

    public static function description(): string
    {
        return __('page-builder::blocks.section.description');
    }

    /**
     * Ratios that produce one track per column.
     *
     * @return array<string, string>
     */
    public static function ratiosFor(int $columns): array
    {
        $columns = max(1, min(4, $columns));

        return match ($columns) {
            1 => ['1' => __('page-builder::blocks.section.full')],
            2 => ['1-1' => '1 / 1', '1-2' => '1 / 2', '2-1' => '2 / 1'],
            3 => ['1-1-1' => '1 / 1 / 1', '1-2-1' => '1 / 2 / 1'],
            4 => ['1-1-1-1' => '1 / 1 / 1 / 1'],
            default => ['1-1' => '1 / 1'],
        };
    }

    /**
     * A ratio that matches `$columns`, falling back when the stored one does not.
     *
     * The two fields used to be independent, so "Three" columns with a leftover
     * `1-1` ratio drew a two-track grid and wrapped the third column onto a new row.
     * A custom ratio — what dragging a column edge on the canvas writes — is kept when
     * it has one track per column.
     */
    public static function ratioFor(int $columns, mixed $ratio): string
    {
        $options = self::ratiosFor($columns);
        $ratio = is_string($ratio) ? $ratio : '';

        if (array_key_exists($ratio, $options) || self::isCustomRatio($columns, $ratio)) {
            return $ratio;
        }

        return array_key_first($options);
    }

    /**
     * Whether `$ratio` is a dragged-to-size layout: one whole-number weight per column.
     */
    public static function isCustomRatio(int $columns, mixed $ratio): bool
    {
        if (! is_string($ratio) || ! preg_match('/^\d{1,3}(?:-\d{1,3})*$/', $ratio)) {
            return false;
        }

        $tracks = array_map('intval', explode('-', $ratio));

        return count($tracks) === max(1, min(4, $columns))
            && min($tracks) >= 1
            && ! array_key_exists($ratio, self::ratiosFor($columns));
    }

    /**
     * Column widths as percentages that add up to 100, from any valid ratio.
     *
     * @return array<int, int>
     */
    public static function percentagesFor(int $columns, mixed $ratio): array
    {
        $tracks = array_map('intval', explode('-', self::ratioFor($columns, $ratio)));
        $total = array_sum($tracks) ?: 1;
        $percentages = array_map(fn (int $track): int => (int) round($track / $total * 100), $tracks);
        $percentages[array_key_last($percentages)] += 100 - array_sum($percentages);

        return $percentages;
    }

    /**
     * The inline `grid-template-columns` for a custom ratio, or null for a preset.
     */
    public static function customTracks(int $columns, mixed $ratio): ?string
    {
        if (! self::isCustomRatio($columns, $ratio)) {
            return null;
        }

        return implode(' ', array_map(fn (string $track): string => "minmax(0, {$track}fr)", explode('-', (string) $ratio)));
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Select::make('columns')
                ->label(__('page-builder::blocks.section.columns'))
                ->options([
                    1 => __('page-builder::blocks.section.one'),
                    2 => __('page-builder::blocks.section.two'),
                    3 => __('page-builder::blocks.section.three'),
                    4 => __('page-builder::blocks.section.four'),
                ])
                ->default(2)
                ->live()
                ->afterStateUpdated(function (mixed $state, callable $set): void {
                    $set('ratio', self::ratioFor((int) $state, null));
                }),
            Select::make('ratio')
                ->label(__('page-builder::blocks.section.ratio'))
                ->helperText(__('page-builder::blocks.section.ratio_hint'))
                ->options(function (callable $get): array {
                    $columns = (int) ($get('columns') ?? 2);
                    $options = self::ratiosFor($columns);
                    $ratio = $get('ratio');

                    if (self::isCustomRatio($columns, $ratio)) {
                        $options[$ratio] = __('page-builder::blocks.section.custom_ratio', [
                            'percent' => implode(' / ', self::percentagesFor($columns, $ratio)).'%',
                        ]);
                    }

                    return $options;
                })
                ->default('1-1'),
            Grid::make(2)->schema([
                Select::make('gap')
                    ->label(__('page-builder::blocks.section.gap'))
                    ->options(self::gaps())
                    ->placeholder(__('page-builder::blocks.common.medium')),
                Select::make('valign')
                    ->label(__('page-builder::blocks.section.valign'))
                    ->options(self::verticalAlignment())
                    ->placeholder(__('page-builder::blocks.section.top')),
            ]),
            Select::make('stack')
                ->label(__('page-builder::blocks.section.stack'))
                ->options([
                    'tablet' => __('page-builder::blocks.section.stack_tablet'),
                    'never' => __('page-builder::blocks.section.stack_never'),
                ])
                ->placeholder(__('page-builder::blocks.section.stack_tablet')),
        ];
    }
}
