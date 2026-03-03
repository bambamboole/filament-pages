<?php declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Page Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used for pages. You can replace this with your own
    | model that extends the default Page model to add custom behavior.
    |
    */
    'model' => \Bambamboole\FilamentPages\Models\Page::class,

    /*
    |--------------------------------------------------------------------------
    | Filament Resource
    |--------------------------------------------------------------------------
    |
    | The Filament resource used for the pages table view. Override this
    | to customise columns, filters, or actions in the table.
    |
    */
    'resource' => \Bambamboole\FilamentPages\Filament\Resources\PageResource::class,

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | Available page layouts. Each class must extend PageLayout and provide
    | a render() method. The first layout is used as the default fallback.
    |
    */
    'layouts' => [
        \Bambamboole\FilamentPages\Layouts\DefaultLayout::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Blocks
    |--------------------------------------------------------------------------
    |
    | Available block types for the page builder. Each class must extend
    | PageBlock and define a Filament form schema and a Blade view.
    |
    */
    'blocks' => [
        \Bambamboole\FilamentPages\Blocks\MarkdownBlock::class,
        \Bambamboole\FilamentPages\Blocks\ImageBlock::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Rendering
    |--------------------------------------------------------------------------
    |
    | Configuration for the CommonMark-based markdown renderer used by
    | the MarkdownBlock. Supports blade blocks, Torchlight syntax
    | highlighting, heading permalinks, and table of contents generation.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | SEO Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback values used when SEO fields are not explicitly filled on a page.
    | The default_og_image should be a path relative to the public directory.
    |
    */
    'seo' => [
        'defaults' => [
            'og_title' => '',
            'og_description' => '',
            'default_og_image' => null, // e.g. 'images/default-og.png'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Configure how frontend page routes are registered. Set a prefix to nest
    | all page routes under a path segment. Define locales as a key-value
    | map (e.g. ['en' => 'English', 'de' => 'Deutsch']) to enable
    | locale-prefixed routing with automatic browser detection.
    |
    */
    'routing' => [
        'prefix' => '',
        'locales' => [],
    ],
];
