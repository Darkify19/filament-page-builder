<?php

use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Filament\Pages\DesignPage;
use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Page;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function page(array $blocks = []): Page
{
    return Page::create(['title' => 'Test page', 'blocks' => $blocks]);
}

function block(string $id, string $type = 'heading', array $data = []): array
{
    return ['id' => $id, 'type' => $type, 'data' => $data];
}

function canvas(Page $page): Testable
{
    return Livewire::test(DesignPage::class, ['record' => $page->getKey()]);
}

/**
 * @return array<int, string>
 */
function ids(Testable $canvas): array
{
    return array_column($canvas->get('blocks'), 'id');
}
