@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\VideoBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;
    use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
    use CarlJanzell\FilamentPageBuilder\Support\EmbedUrl;
    use CarlJanzell\FilamentPageBuilder\Support\MediaUrl;

    $editing = PageBuilder::isEditing();
    $src = MediaUrl::public(is_string($data['src'] ?? null) ? $data['src'] : null) ?? BlockStyle::url($data['url'] ?? null);
    $poster = MediaUrl::public(is_string($data['poster'] ?? null) ? $data['poster'] : null);
    $start = EmbedUrl::seconds($data['start'] ?? null) ?? 0;
    $end = EmbedUrl::seconds($data['end'] ?? null);
    $end = $end !== null && $end > $start ? $end : null;
    $autoplay = ! $editing && (bool) ($data['autoplay'] ?? false);
    $loop = (bool) ($data['loop'] ?? false);
    $ratio = array_key_exists($data['ratio'] ?? '', VideoBlock::RATIOS) ? ($data['ratio'] ?? '') : '';
@endphp

@if ($src)
    <div class="fpb-video" @if ($ratio !== '') data-fpb-ratio="{{ $ratio }}" @endif>
        <video
            src="{{ $src }}{{ $start || $end ? '#t='.$start.($end ? ','.$end : '') : '' }}"
            @if ($poster) poster="{{ $poster }}" @endif
            @if ($data['controls'] ?? true) controls @endif
            @if ($autoplay) autoplay @endif
            @if ($autoplay || ($data['muted'] ?? false)) muted @endif
            @if ($loop && ! $start && ! $end) loop @endif
            playsinline
            preload="metadata"
            @include('page-builder::components.playback', ['start' => $start, 'end' => $end, 'loop' => $loop, 'rate' => 1])
        ></video>
    </div>
@else
    <div class="fpb-embed-placeholder">Upload a video, or paste a link to a video file, in the sidebar.</div>
@endif
