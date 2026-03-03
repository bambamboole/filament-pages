<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Layouts\DefaultLayout;
use Bambamboole\FilamentPages\Models\Page;
use Bambamboole\FilamentPages\Tests\Fixtures\CustomLayout;

it('renders markdown blocks on a published page', function () {
    Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => '**Hello World**']],
    ])->create([
        'title' => 'Block Page',
        'slug' => 'block-page',
    ]);

    $this->get('/block-page')
        ->assertOk()
        ->assertSee('Block Page')
        ->assertSee('<strong>Hello World</strong>', false);
});

it('skips unknown block types gracefully', function () {
    Page::factory()->published()->withBlocks([
        ['type' => 'nonexistent_block', 'data' => ['content' => 'Should not appear']],
        ['type' => 'markdown', 'data' => ['content' => 'Visible content']],
    ])->create([
        'title' => 'Mixed Blocks',
        'slug' => 'mixed-blocks',
    ]);

    $this->get('/mixed-blocks')
        ->assertOk()
        ->assertSee('Visible content')
        ->assertDontSee('Should not appear');
});

it('uses the default layout when page has no layout set', function () {
    Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'Default layout content']],
    ])->create([
        'title' => 'Default Layout',
        'slug' => 'default-layout',
    ]);

    $this->get('/default-layout')
        ->assertOk()
        ->assertSee('Default Layout')
        ->assertSee('Default layout content');
});

it('uses a configured layout when page has layout set', function () {
    config(['filament-pages.layouts' => [
        DefaultLayout::class,
        CustomLayout::class,
    ]]);

    Page::factory()->published()->withLayout('custom')->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'Custom layout content']],
    ])->create([
        'title' => 'Custom Layout',
        'slug' => 'custom-layout',
    ]);

    $this->get('/custom-layout')
        ->assertOk()
        ->assertSee('Custom Layout')
        ->assertSee('Custom layout content');
});

it('falls back to default layout for unknown layout key', function () {
    Page::factory()->published()->withLayout('nonexistent')->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'Fallback content']],
    ])->create([
        'title' => 'Fallback Layout',
        'slug' => 'fallback-layout',
    ]);

    $this->get('/fallback-layout')
        ->assertOk()
        ->assertSee('Fallback Layout')
        ->assertSee('Fallback content');
});

it('renders multiple markdown blocks in order', function () {
    Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'First block']],
        ['type' => 'markdown', 'data' => ['content' => 'Second block']],
    ])->create([
        'title' => 'Multi Block',
        'slug' => 'multi-block',
    ]);

    $this->get('/multi-block')
        ->assertOk()
        ->assertSeeInOrder(['First block', 'Second block']);
});
