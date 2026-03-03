<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Services;

use Bambamboole\FilamentPages\Http\Controllers\LocaleRedirectController;
use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

class FilamentPagesService
{
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
