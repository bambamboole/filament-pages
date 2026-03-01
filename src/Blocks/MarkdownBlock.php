<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Blocks;

use Bambamboole\FilamentPages\Renderer\MarkdownRenderer;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
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
                Select::make('toc_position')
                    ->label('Table of Contents')
                    ->options([
                        'off' => 'Off',
                        'top' => 'Top',
                        'left' => 'Left',
                        'right' => 'Right',
                    ])
                    ->default('off'),
            ]);
    }

    /** {@inheritDoc} */
    public static function mutateData(array $data, ?Model $record = null): array
    {
        $tocPosition = $data['toc_position'] ?? 'off';
        $withToc = $tocPosition !== 'off';

        $renderer = app(MarkdownRenderer::class);
        $result = $renderer->convert($data['content'] ?? '', $withToc);

        $data['content'] = $result->html;
        $data['toc_html'] = $result->tocHtml;
        $data['toc_position'] = $tocPosition;
        $data['front_matter'] = $result->frontMatter;

        return $data;
    }
}
