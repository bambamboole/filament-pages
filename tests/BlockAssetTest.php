<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Blocks\BlockAsset;
use Bambamboole\FilamentPages\Blocks\BlockAssetBag;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Bambamboole\FilamentPages\Models\Page;
use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Bambamboole\FilamentPages\Tests\Fixtures\StyledBlock;
use Illuminate\Support\Facades\Vite;

it('creates a url block asset', function () {
    $asset = BlockAsset::url('/vendor/prism/prism.css');

    expect($asset->content)->toBe('/vendor/prism/prism.css')
        ->and($asset->inline)->toBeFalse();
});

it('creates an inline block asset', function () {
    $asset = BlockAsset::inline('body { color: red; }');

    expect($asset->content)->toBe('body { color: red; }')
        ->and($asset->inline)->toBeTrue();
});

it('renders css url assets as link tags', function () {
    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderStyles())->toContain('<link rel="stylesheet" href="/vendor/prism/prism.css">');
});

it('renders js url assets as script tags', function () {
    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderScripts())->toContain('<script src="/vendor/prism/prism.js"></script>');
});

it('renders inline js assets as inline script tags', function () {
    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderScripts())->toContain('<script>console.log("styled")</script>');
});

it('deduplicates assets by block type', function () {
    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);

    $bag->registerBlock('styled', $block);
    $bag->registerBlock('styled', $block);

    expect(substr_count($bag->renderStyles(), 'prism.css'))->toBe(1)
        ->and(substr_count($bag->renderScripts(), 'prism.js'))->toBe(1);
});

it('produces no output for blocks without assets', function () {
    $bag = new BlockAssetBag;
    $block = app(MarkdownBlock::class);
    $bag->registerBlock('markdown', $block);

    expect($bag->renderStyles())->toBe('')
        ->and($bag->renderScripts())->toBe('');
});

it('includes nonce on inline style and script tags when csp nonce is set', function () {
    Vite::useCspNonce('test-nonce');

    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderStyles())
        ->toContain('<style nonce="test-nonce">.styled { color: red; }</style>')
        ->and($bag->renderScripts())
        ->toContain('<script nonce="test-nonce">console.log("styled")</script>');
});

it('includes nonce on inline script tags when csp nonce is set', function () {
    Vite::useCspNonce('test-nonce');

    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderScripts())
        ->toContain('<script nonce="test-nonce">console.log("styled")</script>')
        ->toContain('<script src="/vendor/prism/prism.js"></script>');
});

it('does not include nonce on external link and script tags', function () {
    Vite::useCspNonce('test-nonce');

    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderStyles())->toContain('<link rel="stylesheet" href="/vendor/prism/prism.css">')
        ->and($bag->renderScripts())->toContain('<script src="/vendor/prism/prism.js"></script>');
});

it('renders without nonce when no csp nonce is set', function () {
    $bag = new BlockAssetBag;
    $block = app(StyledBlock::class);
    $bag->registerBlock('styled', $block);

    expect($bag->renderStyles())->not->toContain('nonce')
        ->and($bag->renderScripts())->not->toContain('nonce')
        ->and($bag->renderScripts())->toContain('<script>console.log("styled")</script>');
});

it('registers assets during renderBlocks', function () {
    app(FilamentPagesService::class)->setBlockClasses([StyledBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'styled', 'data' => ['content' => 'Hello']],
        ['type' => 'styled', 'data' => ['content' => 'World']],
    ])->create([
        'title' => 'Asset Page',
        'slug' => 'asset-page',
    ]);

    $page->renderBlocks();

    $bag = app(BlockAssetBag::class);

    expect($bag->renderStyles())->toContain('prism.css')
        ->and($bag->renderScripts())->toContain('prism.js')
        ->and(substr_count($bag->renderStyles(), 'prism.css'))->toBe(1)
        ->and(substr_count($bag->renderScripts(), 'prism.js'))->toBe(1);
});
