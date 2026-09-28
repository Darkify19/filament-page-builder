@props(['data' => []])

@php
    $url = $data['url'] ?? null;
    $allowed = \CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock::allows(is_string($url) ? $url : null);
@endphp

@if ($allowed)
    <div class="fpb-embed">
        <iframe
            src="{{ $url }}"
            title="Embedded content"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
        ></iframe>
    </div>
@else
    <div class="fpb-embed-placeholder">
        Paste a YouTube, Vimeo or Google Maps URL in the sidebar.
    </div>
@endif
