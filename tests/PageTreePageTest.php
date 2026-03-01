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
