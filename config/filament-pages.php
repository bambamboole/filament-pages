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
    | Built-in Blocks
    |--------------------------------------------------------------------------
    |
    | When true, the package's built-in blocks (Markdown, Image) are included
    | in discovery. Set to false to exclude them and only use your own blocks
    | from the discovery paths below.
    |
    */
    'load_default_blocks' => true,

    /*
    |--------------------------------------------------------------------------
    | Layout Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for layout classes annotated with #[IsLayout].
    | Add your application's layout directories here.
    |
    */
    'layout_discovery_paths' => [
        app_path('Layouts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for block classes annotated with #[IsBlock].
    | Add your application's block directories here.
    |
    */
    'block_discovery_paths' => [
        app_path('Blocks'),
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
            'id_prefix' => 'content',
            'fragment_prefix' => 'content',
            'apply_id_to_heading' => false,
            'heading_class' => '',
            'title' => 'Permalink',
            'aria_hidden' => true,
            'min_heading_level' => 1,
            'max_heading_level' => 6,
        ],
        'external_link' => [
            'internal_hosts' => [],
            'open_in_new_window' => true,
            'html_class' => 'external-link',
            'nofollow' => 'external',
            'noopener' => 'external',
            'noreferrer' => 'external',
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

    /*
    |--------------------------------------------------------------------------
    | Response Cache
    |--------------------------------------------------------------------------
    |
    | Enable HTTP response caching using spatie/laravel-responsecache with
    | the stale-while-revalidate pattern. Cached responses are served
    | instantly while being refreshed in the background after expiry.
    |
    */
    'cache' => [
        'enabled' => env('FILAMENT_PAGES_CACHE_ENABLED', false),
        'lifetime' => 60 * 60,     // 1 hour fresh
        'grace' => 60 * 15,        // 15 min stale-while-revalidate
    ],
];
