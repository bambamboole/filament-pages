<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

function createTempPagesDir(): string
{
    $dir = sys_get_temp_dir().'/filament-pages-test-'.uniqid();
    File::makeDirectory($dir, 0755, true);

    return $dir;
}

function writeYaml(string $dir, string $filename, array $data): void
{
    $path = $dir.'/'.$filename;
    File::ensureDirectoryExists(dirname($path));
    File::put($path, Yaml::dump($data, 4, 2));
}

afterEach(function () {
    $pattern = sys_get_temp_dir().'/filament-pages-test-*';
    foreach (glob($pattern) as $dir) {
        File::deleteDirectory($dir);
    }
});

it('imports a page from YAML file', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'blocks' => [
            ['type' => 'markdown', 'content' => '# Hello World'],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('About')
        ->and($page->slug)->toBe('about')
        ->and($page->slug_path)->toBe('/about')
        ->and($page->published_at->toDateTimeString())->toBe('2024-01-01 00:00:00')
        ->and($page->blocks)->toBe([
            ['type' => 'markdown', 'data' => ['content' => '# Hello World']],
        ]);
});

it('imports page with multiple block types', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'blocks' => [
            ['type' => 'markdown', 'content' => '# Hello'],
            ['type' => 'faq', 'questions' => [
                ['question' => 'What?', 'answer' => 'A page.'],
            ]],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    expect($page->blocks)->toHaveCount(2)
        ->and($page->blocks[0]['type'])->toBe('markdown')
        ->and($page->blocks[0]['data']['content'])->toBe('# Hello')
        ->and($page->blocks[1]['type'])->toBe('faq')
        ->and($page->blocks[1]['data']['questions'])->toHaveCount(1);
});

it('is idempotent', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'blocks' => [
            ['type' => 'markdown', 'content' => '# Hello'],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();
    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful()
        ->expectsOutputToContain('unchanged');

    expect(Page::withoutGlobalScopes()->where('slug', 'about')->count())->toBe(1);
});

it('updates page when content changes', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'blocks' => [
            ['type' => 'markdown', 'content' => '# Hello'],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'blocks' => [
            ['type' => 'markdown', 'content' => '# Updated'],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    expect($page->blocks[0]['data']['content'])->toBe('# Updated');
});

it('resolves slug from filename', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about-us.yaml', [
        'title' => 'About Us',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('title', 'About Us')->first();
    expect($page->slug)->toBe('about-us');
});

it('YAML slug overrides filename', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'foo.yaml', [
        'title' => 'Foo',
        'slug' => 'bar',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('title', 'Foo')->first();
    expect($page->slug)->toBe('bar');
});

it('strips numeric prefix for ordering', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, '03-about.yaml', [
        'title' => 'About',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('title', 'About')->first();
    expect($page->slug)->toBe('about')
        ->and($page->order)->toBe(3);
});

it('imports homepage with slug /', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'home.yaml', [
        'title' => 'Home',
        'slug' => '/',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('title', 'Home')->first();
    expect($page->slug)->toBe('/')
        ->and($page->slug_path)->toBe('/');
});

it('imports hierarchical pages', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
    ]);
    writeYaml($dir, 'about/team.yaml', [
        'title' => 'Team',
        'slug' => 'team',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $parent = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    $child = Page::withoutGlobalScopes()->where('slug', 'team')->first();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->slug_path)->toBe('/about/team');
});

it('imports _index.yaml as directory page', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about/_index.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
    ]);
    writeYaml($dir, 'about/team.yaml', [
        'title' => 'Team',
        'slug' => 'team',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $parent = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    $child = Page::withoutGlobalScopes()->where('slug', 'team')->first();

    expect($parent)->not->toBeNull()
        ->and($parent->parent_id)->toBeNull()
        ->and($parent->slug_path)->toBe('/about')
        ->and($child->parent_id)->toBe($parent->id)
        ->and($child->slug_path)->toBe('/about/team');
});

it('updates SEO data', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'seo' => [
            'title' => 'About - My Site',
            'description' => 'Learn about us',
            'author' => 'John Doe',
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    expect($page->seo->title)->toBe('About - My Site')
        ->and($page->seo->description)->toBe('Learn about us')
        ->and($page->seo->author)->toBe('John Doe');
});

it('imports image block with local file', function () {
    $dir = createTempPagesDir();

    // Create a test image file
    $imagePath = $dir.'/test.jpg';
    File::put($imagePath, str_repeat('x', 100));

    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
        'blocks' => [
            ['type' => 'image', 'file' => './test.jpg', 'alt' => 'Test image'],
        ],
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])->assertSuccessful();

    $page = Page::withoutGlobalScopes()->where('slug', 'about')->first();
    expect($page->blocks[0]['type'])->toBe('image')
        ->and($page->blocks[0]['data'])->toHaveKey('image_collection_id')
        ->and($page->blocks[0]['data'])->not->toHaveKey('file')
        ->and($page->blocks[0]['data']['alt'])->toBe('Test image')
        ->and($page->getMedia($page->blocks[0]['data']['image_collection_id']))->toHaveCount(1);
});

it('prune flag soft-deletes orphan pages', function () {
    // Create a page in the DB that won't exist in the YAML files
    Page::withoutGlobalScopes()->create([
        'type' => 'page',
        'title' => 'Old Page',
        'slug' => 'old-page',
        'slug_path' => '/old-page',
    ]);

    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir, '--prune' => true])
        ->assertSuccessful();

    expect(Page::withoutGlobalScopes()->withTrashed()->where('slug', 'old-page')->first()->trashed())->toBeTrue();
});

it('dry-run makes no DB changes', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'About',
        'slug' => 'about',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir, '--dry-run' => true])
        ->assertSuccessful();

    expect(Page::withoutGlobalScopes()->count())->toBe(0);
});

it('custom path option', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about.yaml', [
        'title' => 'Custom Path Page',
        'slug' => 'custom',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful();

    expect(Page::withoutGlobalScopes()->where('slug', 'custom')->exists())->toBeTrue();
});

it('skips child when parent missing', function () {
    $dir = createTempPagesDir();
    writeYaml($dir, 'about/team.yaml', [
        'title' => 'Team',
        'slug' => 'team',
        'published_at' => '2024-01-01 00:00:00',
    ]);

    $this->artisan('filament-pages:import', ['--path' => $dir])
        ->assertSuccessful()
        ->expectsOutputToContain('parent not found');

    expect(Page::withoutGlobalScopes()->where('slug', 'team')->exists())->toBeFalse();
});
