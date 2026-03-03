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

it('selects a page and sets form state', function () {
    $page = Page::factory()->create(['title' => 'My Page', 'slug' => 'my-page']);

    livewire(PageTreePage::class)
        ->call('selectPage', $page->id)
        ->assertSet('formMode', 'edit')
        ->assertSet('activePageId', $page->id)
        ->assertSet('editPageId', $page->id);
});

it('can edit a page via inline form', function () {
    $page = Page::factory()->create(['title' => 'Old Title', 'slug' => 'old-title']);

    livewire(PageTreePage::class)
        ->call('selectPage', $page->id)
        ->fillForm([
            'title' => 'New Title',
            'slug' => 'new-title',
            'parent_id' => null,
            'published_at' => null,
            'layout' => null,
            'blocks' => [],
        ], 'pageForm')
        ->call('savePage')
        ->assertNotified('Page updated');

    expect($page->fresh()->title)->toBe('New Title')
        ->and($page->fresh()->slug)->toBe('new-title');
});

it('shows error when editing page slug to / while it has children', function () {
    $parent = Page::factory()->create(['title' => 'Parent', 'slug' => 'parent']);
    Page::factory()->withParent($parent)->create(['title' => 'Child', 'slug' => 'child']);

    livewire(PageTreePage::class)
        ->call('selectPage', $parent->id)
        ->fillForm([
            'title' => 'Parent',
            'slug' => '/',
            'parent_id' => null,
            'published_at' => null,
            'layout' => null,
            'blocks' => [],
        ], 'pageForm')
        ->call('savePage')
        ->assertNotified('Cannot set slug to "/" because this page has children');

    expect($parent->fresh()->slug)->toBe('parent');
});

it('can create a page via inline form', function () {
    livewire(PageTreePage::class)
        ->call('startCreatePage')
        ->assertSet('formMode', 'create')
        ->assertSet('activePageId', null)
        ->fillForm([
            'title' => 'Brand New Page',
            'slug' => 'brand-new-page',
            'parent_id' => null,
            'published_at' => null,
            'layout' => null,
            'blocks' => [],
        ], 'pageForm')
        ->call('savePage')
        ->assertNotified('Page created');

    $page = Page::where('title', 'Brand New Page')->first();
    expect($page)->not->toBeNull()
        ->and($page->slug)->toBe('brand-new-page');
});

it('switches to edit mode after creating a page', function () {
    $component = livewire(PageTreePage::class)
        ->call('startCreatePage')
        ->fillForm([
            'title' => 'Created Page',
            'slug' => 'created-page',
            'parent_id' => null,
            'published_at' => null,
            'layout' => null,
            'blocks' => [],
        ], 'pageForm')
        ->call('savePage');

    $page = Page::where('title', 'Created Page')->first();
    $component->assertSet('formMode', 'edit')
        ->assertSet('activePageId', $page->id);
});

it('deselects page and clears form state', function () {
    $page = Page::factory()->create(['title' => 'Test Page']);

    livewire(PageTreePage::class)
        ->call('selectPage', $page->id)
        ->assertSet('formMode', 'edit')
        ->call('deselectPage')
        ->assertSet('formMode', null)
        ->assertSet('activePageId', null)
        ->assertSet('editPageId', null);
});

it('deselects page when active page is deleted', function () {
    $page = Page::factory()->create(['title' => 'Doomed Page']);

    livewire(PageTreePage::class)
        ->call('selectPage', $page->id)
        ->assertSet('activePageId', $page->id)
        ->callAction('deletePage', arguments: ['pageId' => $page->id])
        ->assertSet('activePageId', null)
        ->assertSet('formMode', null);

    expect(Page::find($page->id))->toBeNull();
});

it('rejects reorderTree when pages are nested under homepage', function () {
    $homepage = Page::factory()->home()->create(['title' => 'Home']);
    $about = Page::factory()->create(['title' => 'About', 'slug' => 'about', 'order' => 1]);

    livewire(PageTreePage::class)
        ->call('reorderTree', [
            ['id' => $homepage->id, 'children' => [
                ['id' => $about->id, 'children' => []],
            ]],
        ])
        ->assertNotified('Cannot nest pages under the homepage');

    expect($about->fresh()->parent_id)->toBeNull();
});
