@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $columns = max(1, min(4, (int) ($data['columns'] ?? 2)));
    $ratio = SectionBlock::ratioFor($columns, $data['ratio'] ?? null);
@endphp

<section
    class="fpb-section"
    data-fpb-columns="{{ $columns }}"
    data-fpb-ratio="{{ $ratio }}"
>
    @foreach (PageBuilder::slotNames() as $name)
        {!! PageBuilder::slot($name) !!}
    @endforeach
</section>
