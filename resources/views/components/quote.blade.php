@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $text = is_string($data['text'] ?? null) ? $data['text'] : '';
    $cite = is_string($data['cite'] ?? null) ? $data['cite'] : '';
@endphp

@if (PageBuilder::shows('text', $text))
    <figure class="fpb-quote">
        <blockquote @editable('text')>{!! PageBuilder::text($text) !!}</blockquote>
        @if (PageBuilder::shows('cite', $cite))
            <figcaption @editable('cite')>{!! PageBuilder::text($cite) !!}</figcaption>
        @endif
    </figure>
@endif
