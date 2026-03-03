<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Facades\FilamentPages;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Routing\RouteCollection;

describe('without locales', function () {
    it('shows a published page at its slug path', function () {
        Page::factory()->published()->withBlocks([
            ['type' => 'markdown', 'data' => ['content' => 'About content']],
        ])->create([
            'title' => 'About Us',
            'slug' => 'about',
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About Us')
            ->assertSee('About content');
    });

    it('shows the homepage at the root path', function () {
        Page::factory()->published()->home()->withBlocks([
            ['type' => 'markdown', 'data' => ['content' => 'Welcome home']],
        ])->create([
            'title' => 'Home',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Welcome home');
    });

    it('shows nested pages at their full slug path', function () {
        $parent = Page::factory()->published()->create(['slug' => 'about']);
        Page::factory()->published()->withParent($parent)->create([
            'title' => 'Our Team',
            'slug' => 'team',
        ]);

        $this->get('/about/team')
            ->assertOk()
            ->assertSee('Our Team');
    });

    it('shows pages nested under the homepage at their slug path', function () {
        $homepage = Page::factory()->published()->home()->create(['title' => 'Home']);
        $child = Page::factory()->published()->withParent($homepage)->create([
            'title' => 'Contact',
            'slug' => 'contact',
        ]);

        expect($child->fresh()->slug_path)->toBe('/contact');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Contact');
    });

    it('computes correct slug_path for deeply nested pages under homepage', function () {
        $homepage = Page::factory()->published()->home()->create(['title' => 'Home']);
        $child = Page::factory()->published()->withParent($homepage)->create([
            'title' => 'Services',
            'slug' => 'services',
        ]);
        $grandchild = Page::factory()->published()->withParent($child)->create([
            'title' => 'Consulting',
            'slug' => 'consulting',
        ]);

        expect($child->fresh()->slug_path)->toBe('/services')
            ->and($grandchild->fresh()->slug_path)->toBe('/services/consulting');

        $this->get('/services/consulting')
            ->assertOk()
            ->assertSee('Consulting');
    });

    it('returns 404 for a draft page', function () {
        Page::factory()->draft()->create(['slug' => 'draft-page']);

        $this->get('/draft-page')->assertNotFound();
    });

    it('returns 404 for a page with future published_at', function () {
        Page::factory()->create([
            'slug' => 'future-page',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/future-page')->assertNotFound();
    });

    it('returns 404 for a soft-deleted page', function () {
        $page = Page::factory()->published()->create(['slug' => 'deleted-page']);
        $page->delete();

        $this->get('/deleted-page')->assertNotFound();
    });

    it('returns 404 for a non-existent path', function () {
        $this->get('/does-not-exist')->assertNotFound();
    });
});

describe('with locales', function () {
    beforeEach(function () {
        config()->set('filament-pages.routing.locales', ['en' => 'English', 'de' => 'Deutsch']);

        $router = app('router');
        $newCollection = new RouteCollection;

        foreach ($router->getRoutes()->getRoutes() as $route) {
            if (str_starts_with($route->getName() ?? '', 'filament-pages.')) {
                continue;
            }
            $newCollection->add($route);
        }

        $router->setRoutes($newCollection);

        FilamentPages::routes();
    });

    it('redirects root to default locale', function () {
        $this->get('/')
            ->assertRedirect('/en');
    });

    it('shows the localized homepage at /{locale}', function () {
        Page::factory()->published()->home()->withBlocks([
            ['type' => 'markdown', 'data' => ['content' => 'Welcome']],
        ])->create([
            'title' => 'Home',
            'locale' => 'en',
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Home');
    });

    it('shows the localized homepage for another locale', function () {
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

    it('redirects path without locale prefix to default locale path', function () {
        $this->get('/about')
            ->assertRedirect('/en/about');
    });

    it('redirects invalid locale to default locale then 404s', function () {
        Page::factory()->published()->create([
            'slug' => 'about',
            'locale' => 'en',
        ]);

        $this->get('/fr/about')
            ->assertRedirect('/en/fr/about');

        $this->get('/en/fr/about')
            ->assertNotFound();
    });

    it('shows nested localized pages', function () {
        $parent = Page::factory()->published()->create([
            'slug' => 'about',
            'locale' => 'en',
        ]);
        Page::factory()->published()->withParent($parent)->create([
            'title' => 'Our Team',
            'slug' => 'team',
            'locale' => 'en',
        ]);

        $this->get('/en/about/team')
            ->assertOk()
            ->assertSee('Our Team');
    });
});
