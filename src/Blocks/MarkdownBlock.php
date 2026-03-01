<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class MarkdownBlock extends PageBlock
{
    public static function name(): string
    {
        return 'markdown';
    }

    public static function make(): Block
    {
        return Block::make(static::name())
            ->label('Markdown')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                MarkdownEditor::make('content')
                    ->label('Content')
                    ->required(),
            ]);
    }

    /** {@inheritDoc} */
    public static function mutateData(array $data, ?Model $record = null): array
    {
        $data['content'] = str($data['content'] ?? '')->markdown()->toString();

        return $data;
    }
}
