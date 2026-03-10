@php $renderedBlocks = $page->renderBlocks(); @endphp
<!DOCTYPE html>
<html lang="{{ $page->locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title ?? 'Preview' }}</title>
    @filamentPagesStyles
    @filamentPagesBlockStyles
</head>
<body>
    <main>
        @if($page->title)
            <h1>{{ $page->title }}</h1>
        @endif
        {!! $renderedBlocks !!}
    </main>
    @filamentPagesBlockScripts
</body>
</html>
