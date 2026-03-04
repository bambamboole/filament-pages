<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Models\Page;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    ResponseCache::spy();
});

it('forgets response cache for the page url when a page is saved', function () {
    config()->set('filament-pages.cache.enabled', true);

    Page::factory()->published()->create(['slug' => 'about']);

    ResponseCache::shouldHaveReceived('forget')
        ->with('/about')
        ->once();
});

it('forgets response cache for the page url when a page is deleted', function () {
    $page = Page::factory()->published()->create(['slug' => 'contact']);

    config()->set('filament-pages.cache.enabled', true);
    ResponseCache::spy();

    $page->forceDelete();

    ResponseCache::shouldHaveReceived('forget')
        ->with('/contact');
});

it('does not forget response cache when caching is disabled', function () {
    config()->set('filament-pages.cache.enabled', false);

    Page::factory()->published()->create(['slug' => 'about']);

    ResponseCache::shouldNotHaveReceived('forget');
});

it('forgets response cache with locale prefix when locales are configured', function () {
    config()->set('filament-pages.cache.enabled', true);
    config()->set('filament-pages.routing.locales', ['en' => 'English', 'de' => 'Deutsch']);

    Page::factory()->published()->create(['slug' => 'about', 'locale' => 'en']);

    ResponseCache::shouldHaveReceived('forget')
        ->with('/en/about')
        ->once();
});

it('forgets response cache with routing prefix', function () {
    config()->set('filament-pages.cache.enabled', true);
    config()->set('filament-pages.routing.prefix', 'pages');

    Page::factory()->published()->create(['slug' => 'about']);

    ResponseCache::shouldHaveReceived('forget')
        ->with('pages/about')
        ->once();
});
