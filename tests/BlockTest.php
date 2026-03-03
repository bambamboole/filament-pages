<?php declare(strict_types=1);

use Bambamboole\FilamentPages\Blocks\ImageBlock;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Filament\Forms\Components\Builder\Block;

it('markdown block returns a valid builder block', function () {
    $block = MarkdownBlock::make();

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->getName())->toBe('markdown');
});

it('image block returns a valid builder block', function () {
    $block = ImageBlock::make();

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->getName())->toBe('image');
});
