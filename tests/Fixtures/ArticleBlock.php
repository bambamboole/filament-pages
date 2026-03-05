<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\Blocks\AbstractBlock;
use Bambamboole\FilamentPages\Blocks\IsBlock;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use RalphJSmit\Laravel\SEO\SchemaCollection;

#[IsBlock(type: 'article', label: 'Article')]
class ArticleBlock extends AbstractBlock
{
    protected string $view = 'filament-pages::blocks.markdown';

    public function build(Block $block): Block
    {
        return $block
            ->schema([
                TextInput::make('body'),
            ]);
    }

    #[\Override]
    public function registerSchema(SchemaCollection $schema, array $data, Page $page): SchemaCollection
    {
        return $schema->addArticle();
    }
}
