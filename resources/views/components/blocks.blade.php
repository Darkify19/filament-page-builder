@props(['blocks' => [], 'panel' => null])

@php
    $blocks = \CarlJanzell\FilamentPageBuilder\Support\BlockTree::hydrate($blocks);
    $roots = \CarlJanzell\FilamentPageBuilder\Support\BlockTree::childrenOf($blocks, null);
@endphp

{{-- panel names the panel whose blocks to use. Without it, a public page uses the
     default panel's, which is empty when the plugin lives on a different panel. --}}
<div {{ $attributes->class('fpb-page') }}>
    {!! app(\CarlJanzell\FilamentPageBuilder\BlockRegistries::class)->using($panel, function () use ($roots, $blocks): string {
        $html = '';

        foreach ($roots as $block) {
            $html .= view('page-builder::components.public-block', ['block' => $block, 'blocks' => $blocks])->render();
        }

        return $html;
    }) !!}
</div>
