<?php

namespace CarlJanzell\FilamentPageBuilder\Blocks;

use CarlJanzell\FilamentPageBuilder\Contracts\Container;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
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
    public static function type(): string
    {
        return 'section';
    }

    public static function label(): string
    {
        return 'Section';
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
        return 'A row of columns. Drop other blocks into a column.';
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
            1 => ['1' => 'Full'],
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
     */
    public static function ratioFor(int $columns, mixed $ratio): string
    {
        $options = self::ratiosFor($columns);
        $ratio = is_string($ratio) ? $ratio : '';

        return array_key_exists($ratio, $options) ? $ratio : array_key_first($options);
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Select::make('columns')
                ->label('Columns')
                ->options([
                    1 => 'One',
                    2 => 'Two',
                    3 => 'Three',
                    4 => 'Four',
                ])
                ->default(2)
                ->live()
                ->afterStateUpdated(function (mixed $state, callable $set): void {
                    $set('ratio', self::ratioFor((int) $state, null));
                }),
            Select::make('ratio')
                ->label('Column layout')
                ->options(fn (callable $get): array => self::ratiosFor((int) ($get('columns') ?? 2)))
                ->default('1-1'),
        ];
    }
}
