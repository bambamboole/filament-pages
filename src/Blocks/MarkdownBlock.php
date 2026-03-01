<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Support\Icons\Heroicon;

class MarkdownBlock implements PageBlock
{
    public static function make(): Block
    {
        return Block::make('markdown')
            ->label('Markdown')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                MarkdownEditor::make('content')
                    ->label('Content')
                    ->required(),
            ]);
    }
}
