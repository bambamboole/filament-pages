<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Services;

use Bambamboole\FilamentPages\Blocks\ImageBlock;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Http\Controllers\LocaleRedirectController;
use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Bambamboole\FilamentPages\Layouts\PageLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Facades\Route;

class FilamentPagesService
{
    /** @return class-string<Page> */
    public function model(): string
    {
        return config('filament-pages.model', Page::class);
    }

    /** @return array<class-string<PageBlock>> */
    public function blockClasses(): array
    {
        return config('filament-pages.blocks', [MarkdownBlock::class, ImageBlock::class]);
    }

    /** @return array<class-string<PageLayout>> */
    public function layouts(): array
    {
        return config('filament-pages.layouts', []);
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

    public function routes(string $prefix = ''): void
    {
        $prefix = $prefix ?: config('filament-pages.routing.prefix', '');

        Route::prefix($prefix)->group(function (): void {
            if ($this->hasLocales()) {
                $constraint = implode('|', array_map(preg_quote(...), array_keys($this->locales())));

                Route::get('/', LocaleRedirectController::class)
                    ->name('filament-pages.locale-redirect');

                Route::get('{locale}/{path?}', PageController::class)
                    ->where('locale', $constraint)
                    ->where('path', '.*')
                    ->name('filament-pages.page');

                Route::get('{path}', LocaleRedirectController::class)
                    ->where('path', '.*')
                    ->name('filament-pages.locale-redirect-path');
            } else {
                Route::get('{path?}', PageController::class)
                    ->where('path', '.*')
                    ->name('filament-pages.page');
            }
        });
    }
}
