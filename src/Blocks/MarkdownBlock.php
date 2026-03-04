<?php
declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Bambamboole\FilamentPages\Renderer\MarkdownRenderer;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class MarkdownBlock extends PageBlock
{
    public static string $view = 'filament-pages::blocks.markdown';

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
                Hidden::make('heading_permalinks')->default(false),
                Hidden::make('external_links')->default(true),
                MarkdownEditor::make('content')
                    ->label('Content')
                    ->live(onBlur: true)
                    ->hintActions([
                        Action::make('toggle_heading_permalinks')
                            ->iconButton()
                            ->icon(Heroicon::OutlinedHashtag)
                            ->color(fn (Get $get): string => empty($get('heading_permalinks')) ? 'gray' : 'primary')
                            ->tooltip('Toggle heading permalinks')
                            ->action(fn (Get $get, Set $set): mixed => $set('heading_permalinks', !$get('heading_permalinks'))),
                        Action::make('toggle_external_links')
                            ->iconButton()
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->color(fn (Get $get): string => empty($get('external_links')) ? 'gray' : 'primary')
                            ->tooltip('Toggle external link attributes')
                            ->action(fn (Get $get, Set $set): mixed => $set('external_links', !$get('external_links'))),
                    ])
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
    #[\Override]
    public static function mutateData(array $data, ?Model $record = null): array
    {
        $tocPosition = $data['toc_position'] ?? 'off';
        $withToc = $tocPosition !== 'off';
        $withHeadingPermalinks = (bool) ($data['heading_permalinks'] ?? true);
        $withExternalLinks = (bool) ($data['external_links'] ?? true);

        $renderer = app(MarkdownRenderer::class);
        $result = $renderer->convert(
            $data['content'] ?? '',
            $withToc,
            $withHeadingPermalinks,
            $withExternalLinks,
        );

        $data['content'] = $result->html;
        $data['toc_html'] = $result->tocHtml;
        $data['toc_position'] = $tocPosition;
        $data['front_matter'] = $result->frontMatter;

        return $data;
    }
}
