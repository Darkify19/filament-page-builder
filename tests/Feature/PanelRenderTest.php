<?php

use CarlJanzell\FilamentPageBuilder\Tests\Fixtures\Filament\PageResource;

beforeEach(function (): void {
    PageResource::$canEdit = true;
});

/*
 * The other canvas tests mount the Livewire component on its own. This one goes through
 * the panel route, so Filament's layout renders around the page: a content width or a
 * heading the layout cannot print fails here, not in production.
 */
it('renders the canvas inside the panel layout', function (): void {
    $page = page([block('a', 'heading', ['text' => 'Hello'])]);

    $this->get(PageResource::getUrl('design', ['record' => $page]))
        ->assertOk()
        ->assertSee('fpb-edit-mode', escape: false)
        ->assertSee('fi-main', escape: false);
});
