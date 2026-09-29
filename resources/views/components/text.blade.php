@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $body = is_string($data['body'] ?? null) ? $data['body'] : '';
    // A shortcode may return block markup (a list, a card), which a <p> cannot hold.
    $tag = ! PageBuilder::isEditing() && PageBuilder::shortcodes()->mentions($body) ? 'div' : 'p';
@endphp

<{{ $tag }} class="fpb-text" @editable('body')>{!! PageBuilder::text($body) !!}</{{ $tag }}>
