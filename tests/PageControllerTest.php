<?php

use Bambamboole\FilamentPages\Models\Page;

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

it('returns 404 for a draft page', function () {
    Page::factory()->draft()->create(['slug' => 'draft-page']);

    $this->get('/draft-page')->assertNotFound();
});

it('returns 404 for a non-existent path', function () {
    $this->get('/does-not-exist')->assertNotFound();
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

it('returns 404 for a soft-deleted page', function () {
    $page = Page::factory()->published()->create(['slug' => 'deleted-page']);
    $page->delete();

    $this->get('/deleted-page')->assertNotFound();
});

it('returns 404 for a page with future published_at', function () {
    Page::factory()->create([
        'slug' => 'future-page',
        'published_at' => now()->addDay(),
    ]);

    $this->get('/future-page')->assertNotFound();
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
