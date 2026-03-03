<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages\CreatePage;
use Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages\EditPage;
use Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages\ListPages;
use Bambamboole\FilamentPages\Models\Page;
use Workbench\App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render the list page', function () {
    livewire(ListPages::class)
        ->assertSuccessful();
});

it('can render the create page', function () {
    livewire(CreatePage::class)
        ->assertSuccessful();
});

it('can create a page', function () {
    livewire(CreatePage::class)
        ->fillForm([
            'title' => 'About Us',
            'slug' => 'about-us',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pages', [
        'title' => 'About Us',
        'slug' => 'about-us',
        'slug_path' => '/about-us',
    ]);
});

it('can render the edit page', function () {
    $page = Page::factory()->create();

    livewire(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertSuccessful();
});

it('can update a page', function () {
    $page = Page::factory()->create(['title' => 'Old Title', 'slug' => 'old-title']);

    livewire(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'title' => 'New Title',
            'slug' => 'new-title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh())
        ->title->toBe('New Title')
        ->slug->toBe('new-title')
        ->slug_path->toBe('/new-title');
});

it('can list pages', function () {
    $pages = Page::factory()->count(3)->create();

    livewire(ListPages::class)
        ->assertCanSeeTableRecords($pages);
});

it('shows visit page action for published pages on edit page', function () {
    $page = Page::factory()->published()->create();

    livewire(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionVisible('visitPage');
});

it('hides visit page action for draft pages on edit page', function () {
    $page = Page::factory()->draft()->create();

    livewire(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionHidden('visitPage');
});
