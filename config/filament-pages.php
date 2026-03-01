<?php

// config for Bambamboole/FilamentPages
return [
    'model' => \Bambamboole\FilamentPages\Models\Page::class,
    'resource' => \Bambamboole\FilamentPages\Filament\Resources\PageResource::class,
    'routing' => [
        'enabled' => true,
        'prefix' => '',
        'middleware' => ['web'],
        'locales' => [],
    ],
    'layouts' => [
        \Bambamboole\FilamentPages\Layouts\DefaultLayout::class,
    ],
    'blocks' => [
        \Bambamboole\FilamentPages\Blocks\MarkdownBlock::class,
        \Bambamboole\FilamentPages\Blocks\ImageBlock::class,
    ],
];
