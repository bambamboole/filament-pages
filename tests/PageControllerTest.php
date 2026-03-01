<?php

use Bambamboole\FilamentPages\Models\Page;

it('shows a published page at its slug path', function () {
    Page::factory()->published()->create([
        'title' => 'About Us',
        'slug' => 'about',
        'content' => 'About content',
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
    $page = Page::factory()->published()->create([
        'title' => 'Home',
        'slug' => 'home',
        'content' => 'Welcome home',
    ]);

    $page->slug_path = '/';
    $page->saveQuietly();

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
        'content' => 'Team content',
    ]);

    $this->get('/about/team')
        ->assertOk()
        ->assertSee('Our Team');
});
