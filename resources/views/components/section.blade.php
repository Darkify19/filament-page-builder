@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $columns = max(1, min(4, (int) ($data['columns'] ?? 2)));
    $ratio = SectionBlock::ratioFor($columns, $data['ratio'] ?? null);
    $tracks = SectionBlock::customTracks($columns, $ratio);
    $gap = array_key_exists($data['gap'] ?? '', SectionBlock::GAPS) ? $data['gap'] : null;
    $valign = in_array($data['valign'] ?? null, ['start', 'center', 'end', 'stretch'], true) ? $data['valign'] : null;
    $stack = in_array($data['stack'] ?? null, ['mobile', 'tablet', 'never'], true) ? $data['stack'] : 'mobile';
@endphp

<section
    class="fpb-section"
    data-fpb-columns="{{ $columns }}"
    data-fpb-ratio="{{ $tracks ? 'custom' : $ratio }}"
    data-fpb-stack="{{ $stack }}"
    @if ($gap) data-fpb-gap="{{ $gap }}" @endif
    @if ($valign) data-fpb-valign="{{ $valign }}" @endif
    @if ($tracks) style="grid-template-columns: {{ $tracks }}" @endif
    @if (PageBuilder::isEditing()) data-fpb-tracks="{{ implode('-', SectionBlock::percentagesFor($columns, $ratio)) }}" @endif
>
    @foreach (PageBuilder::slotNames() as $name)
        {!! PageBuilder::slot($name) !!}
    @endforeach
</section>
