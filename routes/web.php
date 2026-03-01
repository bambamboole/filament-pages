<?php

declare(strict_types=1);

use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

$routing = config('filament-pages.routing', []);

if (empty($routing['enabled'])) {
    return;
}

$prefix = $routing['prefix'] ?? '';
$middleware = $routing['middleware'] ?? ['web'];
$locales = $routing['locales'] ?? [];

Route::middleware($middleware)->group(function () use ($prefix, $locales) {
    if (! empty($locales)) {
        $localeConstraint = implode('|', array_map('preg_quote', $locales));

        Route::get(
            rtrim($prefix . '/{locale}', '/'),
            PageController::class
        )
            ->where('locale', $localeConstraint)
            ->name('filament-pages.home');

        Route::get(
            rtrim($prefix . '/{locale}', '/') . '/{path}',
            PageController::class
        )
            ->where('locale', $localeConstraint)
            ->where('path', '.*')
            ->name('filament-pages.page');
    } else {
        $homePath = $prefix ?: '/';

        Route::get($homePath, PageController::class)
            ->name('filament-pages.home');

        Route::get(rtrim($prefix, '/') . '/{path}', PageController::class)
            ->where('path', '.*')
            ->name('filament-pages.page');
    }
});
