<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\Blocks\AbstractBlock;
use Bambamboole\FilamentPages\Blocks\BlockAsset;
use Bambamboole\FilamentPages\Blocks\IsBlock;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;

#[IsBlock(type: 'styled', label: 'Styled')]
class StyledBlock extends AbstractBlock
{
    public function build(Block $block): Block
    {
        return $block
            ->schema([
                TextInput::make('content'),
            ]);
    }

    #[\Override]
    public function render(array $data, Page $page): View
    {
        return view()->make('filament-pages::blocks.markdown', [
            'content' => $data['content'] ?? '',
            'toc_position' => 'none',
            'toc_html' => '',
            'front_matter' => [],
        ]);
    }

    #[\Override]
    public function assets(): array
    {
        return [
            'css' => [
                BlockAsset::url('/vendor/prism/prism.css'),
                BlockAsset::inline('.styled { color: red; }'),
            ],
            'js' => [
                BlockAsset::url('/vendor/prism/prism.js'),
                BlockAsset::inline('console.log("styled")'),
            ],
        ];
    }
}
