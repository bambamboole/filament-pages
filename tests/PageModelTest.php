<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Validation\ValidationException;

it('computes slug_path for root pages', function () {
    $page = Page::factory()->create(['title' => 'About', 'slug' => 'about']);

    expect($page->slug_path)->toBe('/about');
});

it('computes slug_path for nested pages', function () {
    $parent = Page::factory()->create(['title' => 'About', 'slug' => 'about']);
    $child = Page::factory()->withParent($parent)->create(['title' => 'Team', 'slug' => 'team']);

    expect($child->slug_path)->toBe('/about/team');
});

it('computes slug_path for deeply nested pages', function () {
    $root = Page::factory()->create(['slug' => 'about']);
    $child = Page::factory()->withParent($root)->create(['slug' => 'team']);
    $grandchild = Page::factory()->withParent($child)->create(['slug' => 'john']);

    expect($grandchild->slug_path)->toBe('/about/team/john');
});

it('cascades slug_path when parent slug changes', function () {
    $parent = Page::factory()->create(['slug' => 'about']);
    $child = Page::factory()->withParent($parent)->create(['slug' => 'team']);
    $grandchild = Page::factory()->withParent($child)->create(['slug' => 'john']);

    $parent->update(['slug' => 'info']);

    expect($child->fresh()->slug_path)->toBe('/info/team')
        ->and($grandchild->fresh()->slug_path)->toBe('/info/team/john');
});

it('updates slug_path when page is moved to a different parent', function () {
    $about = Page::factory()->create(['slug' => 'about']);
    $services = Page::factory()->create(['slug' => 'services']);
    $child = Page::factory()->withParent($about)->create(['slug' => 'team']);

    expect($child->slug_path)->toBe('/about/team');

    $child->update(['parent_id' => $services->id]);

    expect($child->fresh()->slug_path)->toBe('/services/team');
});

it('cascades slug_path to children when page is moved to a different parent', function () {
    $about = Page::factory()->create(['slug' => 'about']);
    $services = Page::factory()->create(['slug' => 'services']);
    $child = Page::factory()->withParent($about)->create(['slug' => 'team']);
    $grandchild = Page::factory()->withParent($child)->create(['slug' => 'john']);

    $child->update(['parent_id' => $services->id]);

    expect($child->fresh()->slug_path)->toBe('/services/team')
        ->and($grandchild->fresh()->slug_path)->toBe('/services/team/john');
});

it('cascades slug_path when page is moved to root', function () {
    $about = Page::factory()->create(['slug' => 'about']);
    $child = Page::factory()->withParent($about)->create(['slug' => 'team']);
    $grandchild = Page::factory()->withParent($child)->create(['slug' => 'john']);

    $child->update(['parent_id' => null]);

    expect($child->fresh()->slug_path)->toBe('/team')
        ->and($grandchild->fresh()->slug_path)->toBe('/team/john');
});

it('auto-generates slug from title when slug is null', function () {
    $page = Page::factory()->create(['title' => 'Hello World', 'slug' => null]);

    expect($page->slug)->toBe('hello-world')
        ->and($page->slug_path)->toBe('/hello-world');
});

it('computes slug_path as / for home page slug', function () {
    $page = Page::factory()->home()->create(['title' => 'Home']);

    expect($page->slug)->toBe('/')
        ->and($page->slug_path)->toBe('/');
});

it('stores blocks as array', function () {
    $blocks = [['type' => 'markdown', 'data' => ['content' => 'Hello']]];
    $page = Page::factory()->create(['blocks' => $blocks]);

    expect($page->fresh()->blocks)->toBe($blocks);
});

it('allows nullable blocks', function () {
    $page = Page::factory()->create(['blocks' => null]);

    expect($page->fresh()->blocks)->toBeNull();
});

it('identifies a draft page when published_at is null', function () {
    $page = Page::factory()->draft()->create();

    expect($page->isDraft())->toBeTrue()
        ->and($page->isScheduled())->toBeFalse()
        ->and($page->isPublished())->toBeFalse();
});

it('identifies a scheduled page when published_at is in the future', function () {
    $page = Page::factory()->scheduled()->create();

    expect($page->isScheduled())->toBeTrue()
        ->and($page->isDraft())->toBeFalse()
        ->and($page->isPublished())->toBeFalse();
});

it('identifies a published page when published_at is in the past', function () {
    $page = Page::factory()->published()->create();

    expect($page->isPublished())->toBeTrue()
        ->and($page->isDraft())->toBeFalse()
        ->and($page->isScheduled())->toBeFalse();
});

it('generates frontend URL for a published page', function () {
    $page = Page::factory()->published()->create(['slug' => 'about']);

    expect($page->frontendUrl())->toBe(url('/about'));
});

it('generates frontend URL for the homepage', function () {
    $page = Page::factory()->published()->home()->create(['title' => 'Home']);

    expect($page->frontendUrl())->toBe(url('/'));
});

it('excludes homepage from nested parent options', function () {
    $homepage = Page::factory()->home()->create(['title' => 'Home']);
    $about = Page::factory()->create(['title' => 'About', 'slug' => 'about']);

    $options = Page::getNestedOptions();

    expect($options)->toHaveKey($about->id)
        ->not->toHaveKey($homepage->id);
});

it('prevents setting slug to / when page has children', function () {
    $parent = Page::factory()->create(['title' => 'Parent', 'slug' => 'parent']);
    Page::factory()->withParent($parent)->create(['title' => 'Child', 'slug' => 'child']);

    $parent->slug = '/';
    $parent->save();
})->throws(ValidationException::class, 'Cannot set slug to "/" because this page has children.');
