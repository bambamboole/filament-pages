<!DOCTYPE html>
<html lang="{{ $page->locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
</head>
<body>
    <main>
        <h1>{{ $page->title }}</h1>
        <div>{!! str($page->content)->markdown() !!}</div>
    </main>
</body>
</html>
