{{-- The layer behind a block's content: a background video, and the overlay that sits
     between the picture and the text. Colours, gradients and images are plain CSS on the
     wrapper and never reach this view. --}}
@if ($layer)
    <div class="fpb-bg" aria-hidden="true">
        @if (($layer['video']['kind'] ?? null) === 'file')
            @php $video = $layer['video']; @endphp
            <video
                class="fpb-bg-video"
                src="{{ $video['src'] }}{{ $video['start'] || $video['end'] ? '#t='.$video['start'].($video['end'] ? ','.$video['end'] : '') : '' }}"
                autoplay
                muted
                playsinline
                @if ($video['loop'] && ! $video['start'] && ! $video['end']) loop @endif
                preload="auto"
                tabindex="-1"
                @include('page-builder::components.playback', ['start' => $video['start'], 'end' => $video['end'], 'loop' => $video['loop'], 'rate' => $video['rate']])
            ></video>
        @elseif (($layer['video']['kind'] ?? null) === 'iframe')
            <iframe
                class="fpb-bg-frame"
                src="{{ $layer['video']['src'] }}"
                title=""
                tabindex="-1"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="autoplay; encrypted-media; picture-in-picture"
            ></iframe>
        @endif
        @isset($layer['overlay'])
            <div class="fpb-bg-overlay" style="{{ $layer['overlay'] }}"></div>
        @endisset
    </div>
@endif
