@props(['data' => []])

@php
    use CarlJanzell\FilamentPageBuilder\Blocks\CodeBlock;
    use CarlJanzell\FilamentPageBuilder\PageBuilder;

    $html = is_string($data['html'] ?? null) ? $data['html'] : '';
    $css = is_string($data['css'] ?? null) ? $data['css'] : '';
    $js = is_string($data['js'] ?? null) ? $data['js'] : '';
    $editing = PageBuilder::isEditing();
    $markup = PageBuilder::markup($html);

    // The canvas never runs the author's scripts, whichever field they were written in.
    if ($editing) {
        $markup = preg_replace('#<script\b[^>]*>.*?</script\s*>#is', '', $markup) ?? '';
    }
@endphp

<div class="fpb-code">
    @if (trim($css) !== '')
        <style>{!! CodeBlock::stylesheet($css) !!}</style>
    @endif
    {!! $markup !!}
    @if (trim($js) !== '')
        @if ($editing)
            <p class="fpb-code-note">⚡ This block's JavaScript runs in Preview and on the published page.</p>
        @else
            <script>
                (function (root) {
                    var run = function (root) {
{!! CodeBlock::script($js) !!}
                    };
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function () { run.call(root, root); });
                    } else {
                        run.call(root, root);
                    }
                })(document.currentScript.parentElement);
            </script>
        @endif
    @endif
    @if ($editing && trim($html.$css.$js) === '')
        <p class="fpb-placeholder">Write HTML, CSS and JavaScript in the sidebar.</p>
    @endif
</div>
