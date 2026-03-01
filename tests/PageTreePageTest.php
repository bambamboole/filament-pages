<?php

use Bambamboole\FilamentPages\Filament\Pages\PageTreePage;
use Bambamboole\FilamentPages\Models\Page;
use Workbench\App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render the tree page', function () {
    livewire(PageTreePage::class)
        ->assertSuccessful();
});

it('displays pages in the tree', function () {
    $parent = Page::factory()->create(['title' => 'About']);
    $child = Page::factory()->withParent($parent)->create(['title' => 'Team']);

    livewire(PageTreePage::class)
        ->assertSuccessful()
        ->assertSee('About')
        ->assertSee('Team');
});

it('reorders pages via reorderTree', function () {
    $first = Page::factory()->create(['title' => 'First', 'order' => 0]);
    $second = Page::factory()->create(['title' => 'Second', 'order' => 1]);
    $third = Page::factory()->create(['title' => 'Third', 'order' => 2]);

    livewire(PageTreePage::class)
        ->call('reorderTree', [
            ['id' => $third->id, 'children' => []],
            ['id' => $first->id, 'children' => []],
            ['id' => $second->id, 'children' => []],
        ]);

    expect($third->fresh()->order)->toBe(0)
        ->and($first->fresh()->order)->toBe(1)
        ->and($second->fresh()->order)->toBe(2);
});

it('nests pages and updates slug_path via reorderTree', function () {
    $parent = Page::factory()->create(['title' => 'About', 'slug' => 'about', 'order' => 0]);
    $child = Page::factory()->create(['title' => 'Team', 'slug' => 'team', 'order' => 1]);

    expect($child->slug_path)->toBe('/team');

    livewire(PageTreePage::class)
        ->call('reorderTree', [
            ['id' => $parent->id, 'children' => [
                ['id' => $child->id, 'children' => []],
            ]],
        ]);

    expect($child->fresh()->parent_id)->toBe($parent->id)
        ->and($child->fresh()->slug_path)->toBe('/about/team');
});

it('shows Draft badge for unpublished pages', function () {
    Page::factory()->draft()->create(['title' => 'Draft Page']);

    livewire(PageTreePage::class)
        ->assertSee('Draft Page')
        ->assertSee('Draft');
});

it('shows Published badge for published pages', function () {
    Page::factory()->published()->create(['title' => 'Live Page']);

    livewire(PageTreePage::class)
        ->assertSee('Live Page')
        ->assertSee('Published');
});

it('shows Scheduled badge for scheduled pages', function () {
    Page::factory()->scheduled()->create(['title' => 'Future Page']);

    livewire(PageTreePage::class)
        ->assertSee('Future Page')
        ->assertSee('Scheduled');
});

it('can update published_at via updatePublishedAt action', function () {
    $page = Page::factory()->draft()->create(['title' => 'Draft Page']);

    $publishDate = now()->subHour()->format('Y-m-d H:i:s');

    livewire(PageTreePage::class)
        ->callAction('updatePublishedAt', data: [
            'published_at' => $publishDate,
        ], arguments: [
            'pageId' => $page->id,
        ]);

    expect($page->fresh()->published_at->format('Y-m-d H:i:s'))->toBe($publishDate);
});

it('can clear published_at via updatePublishedAt action', function () {
    $page = Page::factory()->published()->create(['title' => 'Published Page']);

    livewire(PageTreePage::class)
        ->callAction('updatePublishedAt', data: [
            'published_at' => null,
        ], arguments: [
            'pageId' => $page->id,
        ]);

    expect($page->fresh()->published_at)->toBeNull();
});
