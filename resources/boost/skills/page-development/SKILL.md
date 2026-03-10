---
name: filament-pages-development
description: Build custom blocks and layouts for the filament-pages content management system.
---

# Filament Pages Development

## When to activate this skill

Activate when working with:
- Creating or editing page blocks (`#[IsBlock]`)
- Creating or editing page layouts (`#[IsLayout]`)
- Block rendering, assets (CSS/JS), or schema markup (JSON-LD)
- Configuring block/layout discovery paths
- Testing blocks or layouts

## Architecture overview

Filament Pages uses **attribute-driven discovery** to find blocks and layouts at runtime.

- **Blocks** are PHP classes annotated with `#[IsBlock(type: '...', label: '...')]` and implement `PageBlock` (or extend `AbstractBlock`). They define a Filament form schema, render HTML, and optionally declare assets and structured data.
- **Layouts** are PHP classes annotated with `#[IsLayout(key: '...', label: '...')]` and implement `PageLayout` (or extend `AbstractLayout`). They wrap the full page response with a Blade template.
- Discovery uses `spatie/laravel-structure-discoverer` to scan configured directories for annotated classes.
- The `Page` model stores blocks as a JSON array of `{type, data}` objects, renders them via the block map, and collects assets into a `BlockAssetBag`.

## Creating a block

### Artisan command

```bash
php artisan filament-pages:make-block Hero --no-interaction
```

### Full annotated example

```php
<?php

declare(strict_types=1);
namespace App\Blocks;

use Bambamboole\FilamentPages\Blocks\AbstractBlock;
use Bambamboole\FilamentPages\Blocks\BlockAsset;
use Bambamboole\FilamentPages\Blocks\IsBlock;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use RalphJSmit\Laravel\SEO\SchemaCollection;

#[IsBlock(type: 'hero', label: 'Hero Section')]
class HeroBlock extends AbstractBlock
{
    // The Blade view rendered by the default render() method.
    protected string $view = 'blocks.hero';

    /**
     * Define the Filament form fields shown in the admin panel.
     * Receives a pre-configured Block builder and must return it.
     */
    public function build(Block $block): Block
    {
        return $block
            ->icon(Heroicon::OutlinedStar)
            ->schema([
                TextInput::make('heading')
                    ->label('Heading')
                    ->required(),
                RichEditor::make('body')
                    ->label('Body')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Override to customise rendering logic.
     * The default implementation simply returns view($this->view, $data).
     */
    #[\Override]
    public function render(array $data, Page $page): View
    {
        return view($this->view, [
            'heading' => $data['heading'] ?? '',
            'body' => $data['body'] ?? '',
            'page' => $page,
        ]);
    }

    /**
     * Declare CSS and JS assets required by this block.
     * Assets are deduplicated even when the block appears multiple times.
     *
     * @return array{css: list<BlockAsset>, js: list<BlockAsset>}
     */
    #[\Override]
    public function assets(): array
    {
        return [
            'css' => [
                BlockAsset::url('/css/hero.css'),
                BlockAsset::inline('.hero { text-align: center; }'),
            ],
            'js' => [
                BlockAsset::url('/js/hero.js'),
            ],
        ];
    }

    /**
     * Contribute JSON-LD structured data for SEO.
     * Only add to the SchemaCollection when relevant; if nothing is added,
     * the package skips schema output for this block.
     */
    #[\Override]
    public function registerSchema(SchemaCollection $schema, array $data, Page $page): SchemaCollection
    {
        return $schema;
    }
}
```

### Blade view template

Block views receive the `$data` array keys as variables (or whatever you pass from `render()`). Place them in `resources/views/blocks/`:

```blade
{{-- resources/views/blocks/hero.blade.php --}}
<section class="hero">
    <h2>{{ $heading }}</h2>
    <div>{!! $body !!}</div>
</section>
```

### IsBlock attribute options

```php
#[IsBlock(
    type: 'hero',           // Unique block type key stored in the JSON column
    label: 'Hero Section',  // Human-readable label (auto-generated from type if omitted)
    translateLabel: false,   // When true, the label is passed through __()
)]
```

## Creating a layout

### Artisan command

```bash
php artisan filament-pages:make-layout Blog --no-interaction
```

### Full annotated example

```php
<?php

declare(strict_types=1);
namespace App\Layouts;

use Bambamboole\FilamentPages\Layouts\AbstractLayout;
use Bambamboole\FilamentPages\Layouts\IsLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

#[IsLayout(key: 'blog', label: 'Blog')]
class BlogLayout extends AbstractLayout
{
    protected string $view = 'layouts.blog';

    #[\Override]
    public function render(Request $request, Page $page): View
    {
        return view($this->view, ['page' => $page]);
    }
}
```

### Layout Blade template

Layout templates must call specific Blade directives to render block assets and content. **Important:** call `$page->renderBlocks()` before the style/script directives so assets are registered first.

```blade
{{-- resources/views/layouts/blog.blade.php --}}
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
```

### Blade directives reference

| Directive | Purpose |
|---|---|
| `@filamentPagesStyles` | Injects the package's base frontend CSS |
| `@filamentPagesBlockStyles` | Renders `<style>` and `<link>` tags collected from block `assets()` |
| `@filamentPagesBlockScripts` | Renders `<script>` tags collected from block `assets()` |

### IsLayout attribute options

```php
#[IsLayout(
    key: 'blog',           // Unique key stored on the Page model's layout column
    label: 'Blog',         // Human-readable label (auto-generated from key if omitted)
    translateLabel: false,  // When true, the label is passed through __()
)]
```

## Block assets

The `BlockAsset` value object represents a single CSS or JS asset. The `BlockAssetBag` collects and deduplicates assets across all blocks on a page.

### BlockAsset factory methods

```php
use Bambamboole\FilamentPages\Blocks\BlockAsset;

// External file — rendered as <link> or <script src="...">
BlockAsset::url('/vendor/prism/prism.css');

// Inline content — rendered as <style>...</style> or <script>...</script>
BlockAsset::inline('.highlight { background: yellow; }');
```

### Returning assets from a block

```php
public function assets(): array
{
    return [
        'css' => [
            BlockAsset::url('/vendor/prism/prism.css'),
            BlockAsset::inline('.styled { color: red; }'),
        ],
        'js' => [
            BlockAsset::url('/vendor/prism/prism.js'),
            BlockAsset::inline('console.log("init")'),
        ],
    ];
}
```

### CSP nonce support

`BlockAssetBag` automatically adds a `nonce` attribute to inline `<style>` and `<script>` tags when `Vite::cspNonce()` returns a value. No manual configuration needed.

## Configuration

Publish the config file with `php artisan vendor:publish --tag=filament-pages-config`.

Key settings in `config/filament-pages.php`:

```php
return [
    // Eloquent model used for pages (extend to customise)
    'model' => \Bambamboole\FilamentPages\Models\Page::class,

    // Include built-in Markdown and Image blocks in discovery
    'load_default_blocks' => true,

    // Directories scanned for #[IsLayout] classes
    'layout_discovery_paths' => [
        app_path('Layouts'),
    ],

    // Directories scanned for #[IsBlock] classes
    'block_discovery_paths' => [
        app_path('Blocks'),
    ],
];
```

## Key interfaces

### PageBlock interface

```php
namespace Bambamboole\FilamentPages\Blocks;

interface PageBlock
{
    public function build(Block $block): Block;

    /** @param array<string, mixed> $data */
    public function registerSchema(SchemaCollection $schema, array $data, Page $page): SchemaCollection;

    /** @param array<string, mixed> $data */
    public function render(array $data, Page $page): View;

    /** @return array{css: list<BlockAsset>, js: list<BlockAsset>} */
    public function assets(): array;
}
```

### PageLayout interface

```php
namespace Bambamboole\FilamentPages\Layouts;

interface PageLayout
{
    public function render(Request $request, Page $page): View;
}
```

### AbstractBlock defaults

Extend `AbstractBlock` to get sensible defaults — only `build()` is abstract:

| Method | Default behaviour |
|---|---|
| `render()` | Returns `view($this->view, $data)` |
| `registerSchema()` | Returns `$schema` unchanged (no markup added) |
| `assets()` | Returns `['css' => [], 'js' => []]` (no assets) |

### AbstractLayout defaults

Extend `AbstractLayout` to get a default `render()` that returns `view($this->view, ['page' => $page])`.

## Testing blocks

Use `FilamentPages::setBlockClasses()` and `FilamentPages::setLayoutClasses()` to override discovery in tests, so only the blocks/layouts you specify are active.

```php
use Bambamboole\FilamentPages\Facades\FilamentPages;

beforeEach(function () {
    FilamentPages::setBlockClasses([
        \App\Blocks\HeroBlock::class,
    ]);
});

afterEach(function () {
    // Reset to re-enable automatic discovery
    FilamentPages::setBlockClasses(null);
});
```

For layout testing:

```php
FilamentPages::setLayoutClasses([
    \App\Layouts\BlogLayout::class,
]);

// Reset after test
FilamentPages::setLayoutClasses(null);
```

### Test fixture pattern

Create test-only block classes in `tests/Fixtures/` with the `#[IsBlock]` attribute. These can be injected via `setBlockClasses()` without polluting the application's block discovery paths.

```php
// tests/Fixtures/StyledBlock.php
namespace Tests\Fixtures;

use Bambamboole\FilamentPages\Blocks\AbstractBlock;
use Bambamboole\FilamentPages\Blocks\BlockAsset;
use Bambamboole\FilamentPages\Blocks\IsBlock;

#[IsBlock(type: 'styled', label: 'Styled')]
class StyledBlock extends AbstractBlock
{
    public function build(Block $block): Block
    {
        return $block->schema([
            TextInput::make('content'),
        ]);
    }

    #[\Override]
    public function assets(): array
    {
        return [
            'css' => [BlockAsset::url('/vendor/prism/prism.css')],
            'js' => [BlockAsset::inline('console.log("styled")')],
        ];
    }
}
```
