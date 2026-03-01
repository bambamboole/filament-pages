<?php

// config for Bambamboole/FilamentPages
return [
    'model' => \Bambamboole\FilamentPages\Models\Page::class,
    'routing' => [
        'enabled' => true,
        'prefix' => '',
        'middleware' => ['web'],
        'locales' => [],
        'layout' => 'filament-pages::layouts.page',
    ],
];
