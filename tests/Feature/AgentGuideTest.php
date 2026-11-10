<?php

use CarlJanzell\FilamentPageBuilder\FilamentPageBuilderPlugin;
use Illuminate\Support\Facades\Blade;

/**
 * The agent guide is what an app's coding agent reads about the plugin. A fluent method
 * that the guide does not name is a method agents will not know exists.
 */
function agentGuidePath(): string
{
    return dirname(__DIR__, 2).'/resources/boost/guidelines/core.blade.php';
}

/** @return array<int, array{0: string}> */
function pluginFluentMethods(): array
{
    return collect((new ReflectionClass(FilamentPageBuilderPlugin::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method): bool => $method->isStatic())
        ->filter(fn (ReflectionMethod $method): bool => $method->getReturnType()?->getName() === 'static')
        ->map(fn (ReflectionMethod $method): array => [$method->getName()])
        ->values()
        ->all();
}

it('finds the plugin fluent methods it checks', function (): void {
    expect(pluginFluentMethods())->toHaveCount(10);
});

it('names every public fluent plugin method in the agent guide', function (string $method): void {
    expect(file_get_contents(agentGuidePath()))->toContain($method.'(');
})->with(fn (): array => pluginFluentMethods());

it('renders the agent guide as Blade without errors', function (): void {
    $html = Blade::render(file_get_contents(agentGuidePath()));

    expect($html)
        ->toContain('composer require carljanzell/filament-page-builder:^0.4.2')
        ->toContain("@editable('field')")
        ->not->toContain('@@')
        ->not->toContain('@verbatim');
});
