{{-- The package stylesheet, for a public page that renders <x-page-builder::blocks>.

     Filament only loads it on panel pages. Without it a section's columns stack, a spacer
     has no height and a background video sits on top of the text instead of behind it. --}}
@php
    try {
        $href = \Filament\Support\Facades\FilamentAsset::getStyleHref('page-builder', \CarlJanzell\FilamentPageBuilder\PageBuilderServiceProvider::PACKAGE);
    } catch (\Throwable) {
        $href = null;
    }
@endphp

@if ($href)
    <link rel="stylesheet" href="{{ $href }}">
@endif
