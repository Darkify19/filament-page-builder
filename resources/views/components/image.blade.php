@props(['data' => []])

@php
    $url = \CarlJanzell\FilamentPageBuilder\Support\MediaUrl::public($data['src'] ?? null);
@endphp

@if (filled($url))
    <figure class="fpb-image">
        <img src="{{ $url }}" alt="{{ $data['alt'] ?? '' }}">
        @if (\CarlJanzell\FilamentPageBuilder\PageBuilder::shows('alt', $data['alt'] ?? ''))
            <figcaption @editable('alt')>{{ $data['alt'] ?? '' }}</figcaption>
        @endif
    </figure>
@else
    <div class="fpb-image-placeholder">Choose an image in the sidebar</div>
@endif
