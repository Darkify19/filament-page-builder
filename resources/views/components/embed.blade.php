@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;
    use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;

    $editing = PageBuilder::isEditing();
    $src = EmbedBlock::src($data, $editing);
    $ratio = array_key_exists($data['ratio'] ?? '', EmbedBlock::RATIOS) ? $data['ratio'] : '16-9';
    $height = is_numeric($data['height'] ?? null) ? max(80, min(2000, (int) $data['height'])) : null;
    $title = filled($data['title'] ?? null) ? $data['title'] : __('page-builder::blocks.embed.title_placeholder');
@endphp

@if ($src)
    <div class="fpb-embed" data-fpb-ratio="{{ $ratio }}" @if ($height) style="height: {{ $height }}px; padding-bottom: 0" @endif>
        <iframe
            src="{{ $src }}"
            title="{{ $title }}"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
            allowfullscreen
        ></iframe>
    </div>
    @if ($editing && ($data['autoplay'] ?? false))
        <p class="fpb-embed-note">{{ __('page-builder::blocks.embed.canvas_autoplay_note') }}</p>
    @endif
@elseif ($editing && filled($data['url'] ?? null))
    <div class="fpb-embed-placeholder">{{ EmbedUrl::problem($data['url']) }}</div>
@else
    <div class="fpb-embed-placeholder">
        Paste a YouTube, Vimeo, Google Maps, Forms or Slides link — or any embed code — in the sidebar.
    </div>
@endif
