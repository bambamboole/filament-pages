<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\Blocks\PageBlock;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use RalphJSmit\Laravel\SEO\SchemaCollection;

class ArticleBlock extends PageBlock
{
    public static string $view = 'filament-pages::blocks.markdown';

    public static function name(): string
    {
        return 'article';
    }

    public static function make(): Block
    {
        return Block::make(static::name())
            ->label('Article')
            ->schema([
                TextInput::make('body'),
            ]);
    }

    #[\Override]
    public static function registerSchema(SchemaCollection $schema, array $data, Model $record): SchemaCollection
    {
        return $schema->addArticle();
    }
}
