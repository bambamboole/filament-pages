<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Services;

use Bambamboole\FilamentPages\Blocks\IsBlock;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Http\Controllers\LocaleRedirectController;
use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Bambamboole\FilamentPages\Layouts\IsLayout;
use Bambamboole\FilamentPages\Layouts\PageLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Facades\Route;
use Spatie\Attributes\Attributes;
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;
use Spatie\StructureDiscoverer\Discover;

class FilamentPagesService
{
    /** @var array<class-string<PageBlock>>|null */
    private ?array $resolvedBlockClasses = null;

    /** @var array<class-string<PageLayout>>|null */
    private ?array $resolvedLayoutClasses = null;

    /** @return class-string<Page> */
    public function model(): string
    {
        return config('filament-pages.model', Page::class);
    }

    /** @return array<class-string<PageBlock>> */
    public function blockClasses(): array
    {
        $paths = config('filament-pages.block_discovery_paths', []);

        if (config('filament-pages.load_default_blocks', true)) {
            array_unshift($paths, dirname(__DIR__).'/Blocks');
        }

        return $this->resolvedBlockClasses ??= Discover::in(...$paths)
            ->classes()->withAttribute(IsBlock::class)->get();
    }

    /**
     * Override discovered block classes (useful for testing).
     *
     * @param  array<class-string<PageBlock>>|null  $classes
     */
    public function setBlockClasses(?array $classes): void
    {
        $this->resolvedBlockClasses = $classes;
    }

    public function resetBlockCache(): void
    {
        $this->resolvedBlockClasses = null;
    }

    /** @return array<class-string<PageLayout>> */
    public function layouts(): array
    {
        $layoutPaths = config()->array('filament-pages.layout_discovery_paths', []);

        return $this->resolvedLayoutClasses ??= Discover::in(...$layoutPaths)
            ->classes()
            ->withAttribute(IsLayout::class)
            ->get();
    }

    public function layoutOptions(): array
    {
        return collect($this->layouts())
            ->mapWithKeys(function (string $class): array {
                $attr = Attributes::get($class, IsLayout::class);

                return [$attr->key => $attr->resolvedLabel()];
            })
            ->toArray();
    }

    /**
     * Override discovered layout classes (useful for testing).
     *
     * @param  array<class-string<PageLayout>>|null  $classes
     */
    public function setLayoutClasses(?array $classes): void
    {
        $this->resolvedLayoutClasses = $classes;
    }

    /** @return array{og_title: string, og_description: string, default_og_image: string|null} */
    public function seoDefaults(): array
    {
        return config('filament-pages.seo.defaults', [
            'og_title' => '',
            'og_description' => '',
            'default_og_image' => null,
        ]);
    }

    /** @return array<string, mixed> */
    public function markdownConfig(): array
    {
        return config('filament-pages.markdown', [
            'blade_blocks' => true,
            'torchlight' => false,
            'heading_permalink' => [],
            'external_link' => [],
            'table_of_contents' => [],
        ]);
    }

    /** @return array<string, string> */
    public function locales(): array
    {
        return config('filament-pages.routing.locales', []);
    }

    public function defaultLocale(): ?string
    {
        $locales = $this->locales();

        return $locales !== [] ? array_key_first($locales) : null;
    }

    public function hasLocales(): bool
    {
        return $this->locales() !== [];
    }

    /** @return array<string> */
    public function cacheMiddleware(): array
    {
        $config = config('filament-pages.cache', [
            'enabled' => false,
            'lifetime' => 3600,
            'grace' => 900,
        ]);

        if (!$config['enabled']) {
            return [];
        }

        return [FlexibleCacheResponse::for($config['lifetime'], $config['grace'])];
    }

    public function routes(): void
    {
        Route::prefix(config('filament-pages.routing.prefix', ''))->group(function (): void {
            if ($this->hasLocales()) {
                $constraint = implode('|', array_map(preg_quote(...), array_keys($this->locales())));

                Route::get('/', LocaleRedirectController::class)
                    ->name('filament-pages.locale-redirect');

                Route::middleware($this->cacheMiddleware())
                    ->get('{locale}/{path?}', PageController::class)
                    ->where('locale', $constraint)
                    ->where('path', '.*')
                    ->name('filament-pages.page');

                Route::get('{path}', LocaleRedirectController::class)
                    ->where('path', '.*')
                    ->fallback()
                    ->name('filament-pages.locale-redirect-path');
            } else {
                Route::middleware($this->cacheMiddleware())
                    ->get('{path?}', PageController::class)
                    ->where('path', '.*')
                    ->fallback()
                    ->name('filament-pages.page');
            }
        });
    }
}
