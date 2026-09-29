@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $code = is_string($data['code'] ?? null) ? $data['code'] : '';
    $shortcodes = PageBuilder::shortcodes();
@endphp

@if (trim($code) !== '')
    <div class="fpb-shortcode">{!! $shortcodes->expand($code) !!}</div>
    @if (PageBuilder::isEditing() && ! $shortcodes->mentions($code))
        <p class="fpb-placeholder">No registered shortcode found in <code>{{ $code }}</code>.</p>
    @endif
@elseif (PageBuilder::isEditing())
    <p class="fpb-placeholder">Type a shortcode such as [year] in the sidebar.</p>
@endif
