<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Blocks\ImageBlock;
use Bambamboole\FilamentPages\Blocks\IsBlock;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Filament\Forms\Components\Builder\Block;
use Spatie\Attributes\Attributes;

it('markdown block returns a valid builder block', function () {
    $attr = Attributes::get(MarkdownBlock::class, IsBlock::class);
    $block = Block::make($attr->type)->label($attr->resolvedLabel());
    $block = app(MarkdownBlock::class)->build($block);

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->getName())->toBe('markdown');
});

it('image block returns a valid builder block', function () {
    $attr = Attributes::get(ImageBlock::class, IsBlock::class);
    $block = Block::make($attr->type)->label($attr->resolvedLabel());
    $block = app(ImageBlock::class)->build($block);

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->getName())->toBe('image');
});
