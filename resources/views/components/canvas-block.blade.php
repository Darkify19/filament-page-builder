@props(['block', 'selectedId' => null])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;
    use CarlJanzell\FilamentPageBuilder\Support\BlockTree;

    $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
    $anchor = BlockTree::isValidAnchor($block['anchor'] ?? null) ? $block['anchor'] : null;
    $label = $block['label'] ?? $block['type'];
@endphp

<div
    class="fpb-block"
    data-id="{{ $block['id'] }}"
    data-parent="{{ $block['parent'] ?? '' }}"
    data-slot="{{ $block['slot'] ?? '' }}"
    data-has-content="{{ $block['hasContent'] ? 'true' : 'false' }}"
    @if ($anchor) id="{{ $anchor }}" @endif
    @if ($block['isContainer'] ?? false) data-container="true" @endif
    @if ($selectedId === $block['id']) data-selected="true" @endif
    @unless ($block['isKnown']) data-unknown="true" @endunless
    @foreach ($settings as $token => $value)
        @if (is_string($token) && preg_match('/^[a-z-]+$/', $token) && is_string($value))
            data-fpb-{{ $token }}="{{ $value }}"
        @endif
    @endforeach
    wire:click.stop="selectBlock('{{ $block['id'] }}')"
    wire:key="fpb-block-{{ $block['id'] }}"
>
    <div class="fpb-block-bar">
        <button
            type="button"
            class="fpb-block-handle"
            title="Drag to move"
            aria-label="Move {{ $label }}"
            draggable="true"
            x-on:dragstart.stop="startMove($event, '{{ $block['id'] }}')"
            x-on:dragend="clearDrag()"
            x-on:click.stop
        >&#8942;&#8942;</button>
        <span class="fpb-block-label">{{ $label }}</span>
        <span class="fpb-block-tools">
            <button type="button" title="Duplicate" aria-label="Duplicate {{ $label }}"
                    wire:click.stop="duplicateBlock('{{ $block['id'] }}')">&#10697;</button>
            <button type="button" title="Delete" aria-label="Delete {{ $label }}"
                    x-on:click.stop="remove('{{ $block['id'] }}', {{ $block['hasContent'] ? 'true' : 'false' }})">&#10005;</button>
        </span>
    </div>

    <div class="fpb-block-body">
        @if ($block['isKnown'] && $block['view'])
            @php
                PageBuilder::editing($block['id'], $block['type'], $block['data'] ?? []);

                if ($block['isContainer'] ?? false) {
                    PageBuilder::provideSlots($block['slotNames'] ?? [], function (string $name) use ($block, $selectedId): string {
                        return view('page-builder::components.canvas-slot', [
                            'parent' => $block['id'],
                            'slot' => $name,
                            'children' => $block['children'][$name] ?? [],
                            'selectedId' => $selectedId,
                        ])->render();
                    });
                }
            @endphp
            {!! PageBuilder::renderSafely(function () use ($block): string {
                if (str_contains((string) $block['view'], '::')) {
                    return view($block['view'], ['data' => $block['data']])->render();
                }

                return view('page-builder::components.dynamic-block', [
                    'view' => $block['view'],
                    'data' => $block['data'],
                ])->render();
            }) !!}
        @elseif (! $block['isKnown'])
            <p class="fpb-block-retired">
                This page holds a <code>{{ $block['type'] }}</code> block, which this
                site no longer offers. Its content is kept and saved untouched; it
                cannot be shown or edited here.
            </p>
        @endif
    </div>
</div>
