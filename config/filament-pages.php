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
    'markdown' => [
        'blade_blocks' => true,
        'torchlight' => false,
        'heading_permalink' => [
            'html_class' => 'heading-permalink',
            'symbol' => '#',
            'insert' => 'after',
        ],
        'table_of_contents' => [
            'html_class' => 'table-of-contents',
            'style' => 'bullet',
            'normalize' => 'relative',
            'min_heading_level' => 2,
            'max_heading_level' => 6,
        ],
    ],
];
