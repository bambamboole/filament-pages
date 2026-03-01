<!DOCTYPE html>
<html lang="{{ $page->locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title ?? 'Preview' }}</title>
    @filamentPagesStyles
</head>
<body>
    <main>
        @if($page->title)
            <h1>{{ $page->title }}</h1>
        @endif
        {!! $page->renderBlocks() !!}
    </main>
</body>
</html>
