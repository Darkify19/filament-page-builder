@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $code = is_string($data['code'] ?? null) ? $data['code'] : '';
    $shortcodes = PageBuilder::shortcodes();
@endphp

@if (trim($code) !== '')
    <div class="fpb-shortcode">{!! $shortcodes->expand($code) !!}</div>
    @if (PageBuilder::isEditing() && ! $shortcodes->mentions($code))
        <p class="fpb-placeholder">{{ PageBuilder::lineWithMarkup('page-builder::blocks.shortcode.canvas_unknown', ['code' => '<code>'.e($code).'</code>']) }}</p>
    @endif
@elseif (PageBuilder::isEditing())
    <p class="fpb-placeholder">{{ __('page-builder::blocks.shortcode.canvas_placeholder') }}</p>
@endif
