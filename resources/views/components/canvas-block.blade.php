@props(['block', 'selectedId' => null])

@php
    use CarlJanzell\FilamentPageBuilder\PageBuilder;
    use CarlJanzell\FilamentPageBuilder\Support\BlockStyle;
    use CarlJanzell\FilamentPageBuilder\Support\BlockTree;

    $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
    $anchor = BlockTree::isValidAnchor($block['anchor'] ?? null) ? $block['anchor'] : null;
    $label = $block['label'] ?? $block['type'];
    $style = BlockStyle::compile(is_array($block['style'] ?? null) ? $block['style'] : []);
    $selected = $selectedId === $block['id'];
    $align = is_array($block['style'] ?? null) ? ($block['style']['text_align'] ?? null) : null;
@endphp

<div
    class="fpb-block"
    data-id="{{ $block['id'] }}"
    data-type="{{ $block['type'] }}"
    data-parent="{{ $block['parent'] ?? '' }}"
    data-slot="{{ $block['slot'] ?? '' }}"
    data-has-content="{{ $block['hasContent'] ? 'true' : 'false' }}"
    @if ($anchor) id="{{ $anchor }}" @endif
    @if ($block['isContainer'] ?? false) data-container="true" @endif
    @if ($selected) data-selected="true" @endif
    @unless ($block['isKnown']) data-unknown="true" @endunless
    @if ($style['sized']) data-fpb-sized="height" @endif
    @if ($style['outer'] !== '') style="{{ $style['outer'] }}" @endif
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
            :draggable="!narrow"
            x-on:dragstart.stop="startMove($event, '{{ $block['id'] }}')"
            x-on:dragend="clearDrag()"
            x-on:click.stop
        >&#8942;&#8942;</button>
        <span class="fpb-block-label">{{ $label }}</span>
        @if ($selected && $block['isKnown'] && ($block['isEditable'] ?? true))
            <span class="fpb-block-quick" role="group" aria-label="Align text">
                @foreach (['left' => 'heroicon-m-bars-3-bottom-left', 'center' => 'heroicon-m-bars-3', 'right' => 'heroicon-m-bars-3-bottom-right', 'justify' => 'heroicon-m-bars-4'] as $side => $icon)
                    <button
                        type="button"
                        title="Align {{ $side }}"
                        aria-label="Align {{ $side }}"
                        aria-pressed="{{ $align === $side ? 'true' : 'false' }}"
                        @if ($align === $side) data-active="true" @endif
                        wire:click.stop="setBlockStyle('{{ $block['id'] }}', 'text_align', '{{ $align === $side ? '' : $side }}')"
                    ><x-filament::icon :icon="$icon" class="fpb-quick-icon" /></button>
                @endforeach
            </span>
        @endif
        <span class="fpb-block-tools">
            <button
                type="button"
                class="fpb-block-nudge"
                title="Move up"
                aria-label="Move {{ $label }} up"
                x-show="narrow"
                x-cloak
                x-on:click.stop="moveSelected('{{ $block['id'] }}', -1)"
            >↑</button>
            <button
                type="button"
                class="fpb-block-nudge"
                title="Move down"
                aria-label="Move {{ $label }} down"
                x-show="narrow"
                x-cloak
                x-on:click.stop="moveSelected('{{ $block['id'] }}', 1)"
            >↓</button>
            <button
                type="button"
                class="fpb-block-edit"
                title="Edit"
                aria-label="Edit {{ $label }}"
                x-show="narrow"
                x-cloak
                x-on:click.stop="showWorkspace('settings')"
            >Edit</button>
            <button type="button" title="Duplicate" aria-label="Duplicate {{ $label }}"
                    wire:click.stop="duplicateBlock('{{ $block['id'] }}')">&#10697;</button>
            <button type="button" class="fpb-block-more" title="More actions (right-click)" aria-label="More actions for {{ $label }}"
                    x-on:click.stop="openMenu($event, '{{ $block['id'] }}')">&#8943;</button>
            <button type="button" title="Delete" aria-label="Delete {{ $label }}"
                    x-on:click.stop="remove('{{ $block['id'] }}', {{ $block['hasContent'] ? 'true' : 'false' }})">&#10005;</button>
        </span>
    </div>

    <div
        class="fpb-block-body{{ $style['class'] ? ' '.$style['class'] : '' }}"
        @if ($style['inner'] !== '') style="{{ $style['inner'] }}" @endif
    >
        @include('page-builder::components.background', ['layer' => $style['background']])
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

    @if ($selected && $block['isKnown'])
        <span
            class="fpb-resize fpb-resize-x"
            title="Drag to change the width. Double-click to reset."
            aria-hidden="true"
            x-on:pointerdown.stop.prevent="startResize($event, '{{ $block['id'] }}', 'width')"
            x-on:dblclick.stop="resetSize('{{ $block['id'] }}', 'width')"
            x-on:click.stop
        ></span>
        <span
            class="fpb-resize fpb-resize-y"
            title="Drag to change the height. Double-click to reset."
            aria-hidden="true"
            x-on:pointerdown.stop.prevent="startResize($event, '{{ $block['id'] }}', 'height')"
            x-on:dblclick.stop="resetSize('{{ $block['id'] }}', 'height')"
            x-on:click.stop
        ></span>
    @endif
</div>
