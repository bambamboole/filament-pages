<?php

use Bambamboole\FilamentPages\Http\Controllers\PageController;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $router = app('router');
    $newCollection = new RouteCollection;

    foreach ($router->getRoutes()->getRoutes() as $route) {
        if (str_starts_with($route->getName() ?? '', 'filament-pages.')) {
            continue;
        }
        $newCollection->add($route);
    }

    $router->setRoutes($newCollection);

    Route::middleware(['web'])->group(function () {
        Route::get('{locale}', PageController::class)
            ->where('locale', 'en|de')
            ->name('filament-pages.home');

        Route::get('{locale}/{path}', PageController::class)
            ->where('locale', 'en|de')
            ->where('path', '.*')
            ->name('filament-pages.page');
    });
});

it('shows a localized page at /{locale}/{path}', function () {
    Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'English about']],
    ])->create([
        'title' => 'About',
        'slug' => 'about',
        'locale' => 'en',
    ]);

    $this->get('/en/about')
        ->assertOk()
        ->assertSee('About')
        ->assertSee('English about');
});

it('shows the localized homepage at /{locale}', function () {
    Page::factory()->published()->home()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'Willkommen']],
    ])->create([
        'title' => 'Startseite',
        'locale' => 'de',
    ]);

    $this->get('/de')
        ->assertOk()
        ->assertSee('Startseite');
});

it('returns 404 for a path without locale prefix', function () {
    Page::factory()->published()->create([
        'slug' => 'about',
        'locale' => 'en',
    ]);

    $this->get('/about')->assertNotFound();
});

it('returns 404 for an invalid locale segment', function () {
    Page::factory()->published()->create([
        'slug' => 'about',
        'locale' => 'en',
    ]);

    $this->get('/fr/about')->assertNotFound();
});
