@props(['data' => []])

@php
    $href = \CarlJanzell\FilamentPageBuilder\Support\SafeUrl::href($data['url'] ?? null);
@endphp

<a
    class="fpb-button"
    href="{{ $href }}"
    @editable('label')
>{!! \CarlJanzell\FilamentPageBuilder\PageBuilder::text($data['label'] ?? '') !!}</a>
