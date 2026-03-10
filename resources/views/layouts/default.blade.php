@php $renderedBlocks = $page->renderBlocks(); @endphp
<!DOCTYPE html>
<html lang="{{ $page->locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {!! seo($page) !!}
    @filamentPagesStyles
    @filamentPagesBlockStyles
</head>
<body>
    <main>
        <h1>{{ $page->title }}</h1>
        {!! $renderedBlocks !!}
    </main>
    @filamentPagesBlockScripts
</body>
</html>
