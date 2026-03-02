<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

class FilamentPages
{
    /**
     * Register page frontend routes. Call this at the END of your routes/web.php.
     * No default middleware — routes/web.php already applies 'web'.
     *
     * @param  array<string, string>  $locales  e.g. ['en' => 'English', 'de' => 'Deutsch']
     */
    public static function routes(
        string $prefix = '',
        array $locales = [],
    ): void {
        Route::prefix($prefix)->group(function () use ($locales): void {
            if ($locales !== []) {
                $localeConstraint = implode('|', array_map(preg_quote(...), array_keys($locales)));

                Route::get('{locale}', PageController::class)
                    ->where('locale', $localeConstraint)
                    ->name('filament-pages.home');

                Route::get('{locale}/{path}', PageController::class)
                    ->where('locale', $localeConstraint)
                    ->where('path', '.*')
                    ->name('filament-pages.page');
            } else {
                Route::fallback(PageController::class)->name('filament-pages.page');
            }
        });
    }
}
