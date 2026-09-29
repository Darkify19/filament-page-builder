@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\ListBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $items = ListBlock::itemsFrom($data['items'] ?? null);
    $marker = array_key_exists($data['marker'] ?? '', ListBlock::MARKERS) ? $data['marker'] : 'bullet';
    $tag = $marker === 'number' ? 'ol' : 'ul';
@endphp

@if ($items !== [])
    <{{ $tag }} class="fpb-list" data-fpb-marker="{{ $marker }}">
        @foreach ($items as $item)
            <li>{!! PageBuilder::shortcodes()->expand($item) !!}</li>
        @endforeach
    </{{ $tag }}>
@elseif (PageBuilder::isEditing())
    <p class="fpb-placeholder">Add the list's items in the sidebar, one per line.</p>
@endif
