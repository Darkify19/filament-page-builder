@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\ImageBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;
    use CarlJanzell\FilamentPageBuilder\Support\MediaUrl;

    $url = MediaUrl::public($data['src'] ?? null);
    $overlay = ImageBlock::overlayStyle($data);
@endphp

@if (filled($url))
    <figure class="fpb-image" @if ($overlay) data-fpb-overlay="true" @endif>
        <span class="fpb-image-frame">
            <img src="{{ $url }}" alt="{{ $data['alt'] ?? '' }}">
            @if ($overlay)
                <span class="fpb-image-overlay" style="{{ $overlay }}" aria-hidden="true"></span>
            @endif
        </span>
        @if (PageBuilder::shows('alt', $data['alt'] ?? ''))
            <figcaption @editable('alt')>{{ $data['alt'] ?? '' }}</figcaption>
        @endif
    </figure>
@else
    <div class="fpb-image-placeholder">Choose an image in the sidebar</div>
@endif
