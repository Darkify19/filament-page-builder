<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview: {{ $title }}</title>
    <x-page-builder::styles />
    <style>
        html, body { margin: 0; padding: 0; }
        body { background: var(--fpb-canvas-bg, #fff); font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; line-height: 1.5; }
        /* The canvas styles view is scoped to .fpb-canvas, so the page wears that class
           to pick up the site's tokens — without the editor's frame around it. */
        .fpb-canvas.fpb-preview-page {
            border: 0; border-radius: 0; box-shadow: none;
            overflow: visible; min-height: 100vh; max-width: none;
        }
    </style>
</head>
<body class="fpb-preview-body">
    <div class="fpb-canvas fpb-preview-page">
        @if ($stylesView)
            @include($stylesView)
        @endif
        <x-page-builder::blocks :blocks="$blocks" />
    </div>
</body>
</html>
