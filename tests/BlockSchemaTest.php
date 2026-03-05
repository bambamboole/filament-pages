<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Bambamboole\FilamentPages\Models\Page;
use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Bambamboole\FilamentPages\Tests\Fixtures\ArticleBlock;
use Bambamboole\FilamentPages\Tests\Fixtures\FaqBlock;
use RalphJSmit\Laravel\SEO\Schema\ArticleSchema;
use RalphJSmit\Laravel\SEO\Schema\FaqPageSchema;

it('returns no schema when page has no blocks', function () {
    $page = Page::factory()->published()->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->toBeNull();
});

it('returns no schema when blocks do not override registerSchema', function () {
    app(FilamentPagesService::class)->setBlockClasses([MarkdownBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'markdown', 'data' => ['content' => 'Hello']],
    ])->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->toBeNull();
});

it('collects faq schema from a block that registers it', function () {
    app(FilamentPagesService::class)->setBlockClasses([MarkdownBlock::class, FaqBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'faq', 'data' => ['questions' => []]],
    ])->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->not->toBeNull()
        ->and($seoData->schema->markup)->toHaveKey(FaqPageSchema::class);
});

it('collects article schema from a block that registers it', function () {
    app(FilamentPagesService::class)->setBlockClasses([ArticleBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'article', 'data' => ['body' => 'Some article content']],
    ])->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->not->toBeNull()
        ->and($seoData->schema->markup)->toHaveKey(ArticleSchema::class);
});

it('aggregates schema from multiple blocks', function () {
    app(FilamentPagesService::class)->setBlockClasses([FaqBlock::class, ArticleBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'faq', 'data' => ['questions' => []]],
        ['type' => 'article', 'data' => ['body' => 'Content']],
    ])->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->not->toBeNull()
        ->and($seoData->schema->markup)->toHaveKey(FaqPageSchema::class)
        ->and($seoData->schema->markup)->toHaveKey(ArticleSchema::class);
});

it('skips unknown block types when collecting schema', function () {
    app(FilamentPagesService::class)->setBlockClasses([FaqBlock::class]);

    $page = Page::factory()->published()->withBlocks([
        ['type' => 'nonexistent', 'data' => []],
        ['type' => 'faq', 'data' => ['questions' => []]],
    ])->create();

    $seoData = $page->getDynamicSEOData();

    expect($seoData->schema)->not->toBeNull()
        ->and($seoData->schema->markup)->toHaveKey(FaqPageSchema::class);
});
