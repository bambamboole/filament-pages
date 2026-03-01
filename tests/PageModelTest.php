<?php

use Bambamboole\FilamentPages\Models\Page;

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

it('auto-generates slug from title when slug is empty', function () {
    $page = Page::factory()->create(['title' => 'Hello World', 'slug' => '']);

    expect($page->slug)->toBe('hello-world')
        ->and($page->slug_path)->toBe('/hello-world');
});

it('stores blocks as array', function () {
    $blocks = [['type' => 'markdown', 'data' => ['content' => 'Hello']]];
    $page = Page::factory()->create(['blocks' => $blocks]);

    expect($page->fresh()->blocks)->toBe($blocks);
});

it('allows nullable content and blocks', function () {
    $page = Page::factory()->create(['content' => null, 'blocks' => null]);

    expect($page->fresh()->content)->toBeNull()
        ->and($page->fresh()->blocks)->toBeNull();
});
