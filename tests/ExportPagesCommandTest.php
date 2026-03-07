<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

function createTempExportDir(): string
{
    $dir = sys_get_temp_dir().'/filament-pages-export-test-'.uniqid();
    File::makeDirectory($dir, 0755, true);

    return $dir;
}

afterEach(function () {
    $pattern = sys_get_temp_dir().'/filament-pages-export-test-*';
    foreach (glob($pattern) as $dir) {
        File::deleteDirectory($dir);
    }
});

it('exports a simple page to YAML', function () {
    $dir = createTempExportDir();

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => '# Hello World']],
    ])->create([
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    $file = $dir.'/about.yaml';
    expect(file_exists($file))->toBeTrue();

    $data = Yaml::parseFile($file);
    expect($data['title'])->toBe('About')
        ->and($data['slug'])->toBe('about')
        ->and($data['published_at'])->toBe('2024-01-01 00:00:00')
        ->and($data['blocks'])->toHaveCount(1)
        ->and($data['blocks'][0]['type'])->toBe('markdown')
        ->and($data['blocks'][0]['content'])->toBe('# Hello World');
});

it('denormalizes blocks without data wrapper', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => '# Test']],
        ['type' => 'faq', 'data' => ['questions' => [['q' => 'Why?', 'a' => 'Because.']]]],
    ])->create(['title' => 'Test', 'slug' => 'test']);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    $data = Yaml::parseFile($dir.'/test.yaml');
    expect($data['blocks'][0])->not->toHaveKey('data')
        ->and($data['blocks'][0]['content'])->toBe('# Test')
        ->and($data['blocks'][1])->not->toHaveKey('data')
        ->and($data['blocks'][1]['questions'])->toHaveCount(1);
});

it('uses _index.yaml for hierarchical pages', function () {
    $dir = createTempExportDir();

    $parent = Page::factory()->published()->create([
        'title' => 'About',
        'slug' => 'about',
    ]);
    Page::factory()->published()->withParent($parent)->create([
        'title' => 'Team',
        'slug' => 'team',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    expect(file_exists($dir.'/about/_index.yaml'))->toBeTrue()
        ->and(file_exists($dir.'/about/team.yaml'))->toBeTrue();
});

it('adds order prefix in filename', function () {
    $dir = createTempExportDir();

    $page = Page::factory()->published()->create([
        'title' => 'Services',
        'slug' => 'services',
    ]);
    $page->updateQuietly(['order' => 3]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    expect(file_exists($dir.'/03-services.yaml'))->toBeTrue();
});

it('has no order prefix when order is 0', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->create([
        'title' => 'About',
        'slug' => 'about',
        'order' => 0,
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    expect(file_exists($dir.'/about.yaml'))->toBeTrue();
});

it('exports homepage', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->home()->create([
        'title' => 'Home',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    expect(file_exists($dir.'/home.yaml'))->toBeTrue();

    $data = Yaml::parseFile($dir.'/home.yaml');
    expect($data['slug'])->toBe('/');
});

it('exports SEO data', function () {
    $dir = createTempExportDir();

    $page = Page::factory()->published()->create([
        'title' => 'About',
        'slug' => 'about',
    ]);

    $page->seo()->updateOrCreate([], [
        'title' => 'About - My Site',
        'description' => 'Learn about us',
        'author' => 'John Doe',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    $data = Yaml::parseFile($dir.'/about.yaml');
    expect($data)->toHaveKey('seo')
        ->and($data['seo']['title'])->toBe('About - My Site')
        ->and($data['seo']['description'])->toBe('Learn about us')
        ->and($data['seo']['author'])->toBe('John Doe');
});

it('filters by locale', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->create([
        'title' => 'English Page',
        'slug' => 'english',
        'locale' => 'en',
    ]);
    Page::factory()->published()->create([
        'title' => 'German Page',
        'slug' => 'german',
        'locale' => 'de',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir, '--locale' => 'en'])
        ->assertSuccessful();

    expect(file_exists($dir.'/english.yaml'))->toBeTrue()
        ->and(file_exists($dir.'/german.yaml'))->toBeFalse();
});

it('filters by type', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->create([
        'title' => 'A Page',
        'slug' => 'a-page',
        'type' => 'page',
    ]);
    Page::factory()->published()->create([
        'title' => 'A Post',
        'slug' => 'a-post',
        'type' => 'post',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir, '--type' => 'page'])
        ->assertSuccessful();

    expect(file_exists($dir.'/a-page.yaml'))->toBeTrue()
        ->and(file_exists($dir.'/a-post.yaml'))->toBeFalse();
});

it('round-trips export then import', function () {
    $dir = createTempExportDir();

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => '# Round Trip']],
    ])->create([
        'title' => 'Round Trip',
        'slug' => 'round-trip',
        'published_at' => '2024-06-15 12:00:00',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    // Clear the database
    Page::withoutGlobalScopes()->forceDelete();
    expect(Page::withoutGlobalScopes()->count())->toBe(0);

    // Import back
    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful();

    $imported = Page::withoutGlobalScopes()->where('slug', 'round-trip')->first();
    expect($imported)->not->toBeNull()
        ->and($imported->title)->toBe('Round Trip')
        ->and($imported->slug)->toBe('round-trip')
        ->and($imported->published_at->toDateTimeString())->toBe('2024-06-15 12:00:00')
        ->and($imported->blocks)->toHaveCount(1)
        ->and($imported->blocks[0]['type'])->toBe('markdown')
        ->and($imported->blocks[0]['data']['content'])->toBe('# Round Trip');
});

it('skips trashed pages', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->create([
        'title' => 'Active',
        'slug' => 'active',
    ]);
    Page::factory()->published()->create([
        'title' => 'Trashed',
        'slug' => 'trashed',
        'deleted_at' => now(),
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    expect(file_exists($dir.'/active.yaml'))->toBeTrue()
        ->and(file_exists($dir.'/trashed.yaml'))->toBeFalse();
});

it('exports layout', function () {
    $dir = createTempExportDir();

    Page::factory()->published()->withLayout('sidebar')->create([
        'title' => 'With Layout',
        'slug' => 'with-layout',
    ]);

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful();

    $data = Yaml::parseFile($dir.'/with-layout.yaml');
    expect($data)->toHaveKey('layout')
        ->and($data['layout'])->toBe('sidebar');
});

it('handles empty database', function () {
    $dir = createTempExportDir();

    $this->artisan('filament-pages:export', ['--path' => $dir])
        ->assertSuccessful()
        ->expectsOutputToContain('No pages found');
});
