# Filament Pages

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/filament-pages.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-pages)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/filament-pages/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bambamboole/filament-pages/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/filament-pages.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-pages)

A Filament plugin for managing hierarchical, block-based content pages. Features a drag-and-drop page tree, extensible block system (Markdown, Image out of the box), nested pages with automatic slug path computation, multi-locale support, SEO integration, and live preview.

## Installation

```bash
composer require bambamboole/filament-pages
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-pages-migrations"
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="filament-pages-config"
```

Register the plugin in your Filament panel provider:

```php
use Bambamboole\FilamentPages\FilamentPagesPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentPagesPlugin::make(),
        ]);
}
```

Add the plugin views to your Tailwind CSS content paths:

```css
@source '../../../../vendor/bambamboole/filament-pages/resources/**/*.blade.php';
```

## Configuration

The plugin is configured through the fluent API on `FilamentPagesPlugin` and the published config file.

```php
FilamentPagesPlugin::make()
    ->seoForm(fn () => [
        TextInput::make('canonical_url'),
    ])
    ->treeItemActions(fn (PageTreePage $page) => [
        Action::make('duplicate')->action(fn (array $arguments) => /* ... */),
    ])
    ->previewView('my-custom-preview-view')
```

Blocks and layouts are configured in the `config/filament-pages.php` config file:

```php
// config/filament-pages.php
'blocks' => [
    \Bambamboole\FilamentPages\Blocks\MarkdownBlock::class,
    \Bambamboole\FilamentPages\Blocks\ImageBlock::class,
    \App\Blocks\MyCustomBlock::class,
],

'layouts' => [
    \Bambamboole\FilamentPages\Layouts\DefaultLayout::class,
    \App\Layouts\LandingPageLayout::class,
],
```

### Blocks

Register block types in the `blocks` config array. Each block must extend `Bambamboole\FilamentPages\Blocks\PageBlock`.

Two blocks ship out of the box:
- **MarkdownBlock** — Rich markdown editor with optional table of contents (top/left/right positioning), front matter parsing, and Torchlight syntax highlighting.
- **ImageBlock** — Spatie Media Library file upload with responsive images and an image editor.

### Layouts

Layouts control how pages render on the frontend. Each layout implements `Bambamboole\FilamentPages\Layouts\PageLayout`. Register them in the `layouts` config array.

### Multi-Locale

Enable multi-language content by configuring locales in the config file:

```php
// config/filament-pages.php
'routing' => [
    'prefix' => '',
    'locales' => ['en' => 'English', 'de' => 'Deutsch'],
],
```

When locales are enabled, the page tree filters by locale and frontend routes include a `{locale}` prefix.

### SEO

SEO is always enabled. Every page form includes an SEO tab (powered by `ralphjsmit/laravel-filament-seo`).

Extend the SEO form with custom fields:

```php
->seoForm(fn () => [
    TextInput::make('canonical_url'),
])
```

### Preview

Live preview is always enabled (powered by `pboivin/filament-peek`). To use a custom preview view:

```php
->previewView('my-custom-preview-view')
```

## Creating Custom Blocks

Generate a block stub with the Artisan command:

```bash
php artisan filament-pages:make-block MyCustomBlock
```

A custom block extends `PageBlock` and defines a name, a Filament form schema, and optionally a `mutateData()` method to transform data before rendering:

```php
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;

class CallToActionBlock extends PageBlock
{
    public static function name(): string
    {
        return 'call-to-action';
    }

    public static function make(): Block
    {
        return Block::make(static::name())
            ->label('Call to Action')
            ->schema([
                TextInput::make('heading')->required(),
                TextInput::make('button_text')->required(),
                TextInput::make('button_url')->url()->required(),
            ]);
    }

    public static function viewName(): string
    {
        return 'blocks.call-to-action';
    }
}
```

## Creating Custom Layouts

Generate a layout stub:

```bash
php artisan filament-pages:make-layout LandingPageLayout
```

A layout implements `PageLayout` and returns a view:

```php
use Bambamboole\FilamentPages\Layouts\PageLayout;

class LandingPageLayout implements PageLayout
{
    public static function name(): string { return 'landing'; }
    public static function label(): string { return 'Landing Page'; }

    public function render(Request $request, Page $page): View
    {
        return view('layouts.landing', ['page' => $page]);
    }
}
```

Include `@filamentPagesStyles` in your layout's `<head>` to load the frontend CSS:

```blade
<head>
    @filamentPagesStyles
</head>
```

## Route Registration

Register the frontend page routes in your `routes/web.php`:

```php
use Bambamboole\FilamentPages\Facades\FilamentPages;

FilamentPages::routes();
```

This registers catch-all routes that resolve pages by their slug path. You can pass a prefix:

```php
FilamentPages::routes('pages'); // all pages under /pages/*
```

When locales are configured, routes are automatically prefixed with `{locale}`.

## Frontend Rendering

Pages are rendered through their assigned layout using `{!! $page->renderBlocks() !!}`.

You can also render blocks programmatically:

```php
$page = Page::where('slug_path', '/about')->first();
echo $page->renderBlocks();
```

## Page Tree

The plugin provides an interactive page tree at `/page-tree` in your Filament panel. You can:
- Drag and drop to reorder and nest pages
- Create, edit, and delete pages via slide-over modals
- Publish/unpublish with datetime scheduling
- Switch between locales
- Preview pages before publishing

Custom actions can be added to tree items:

```php
FilamentPagesPlugin::make()
    ->treeItemActions(fn (PageTreePage $page) => [
        Action::make('duplicate')->action(fn (array $arguments) => /* ... */),
    ])
```

## Authorization

The package supports Laravel policies for access control. Create a policy for your page model to restrict actions:

```bash
php artisan make:policy PagePolicy --model=Page
```

Supported abilities: `create`, `update`, `delete`, `reorder`.

The package is **permissive by default** — when no policy is registered, all actions are allowed. Once a policy exists, only explicitly allowed abilities are permitted.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Manuel Christlieb](https://github.com/bambamboole)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
