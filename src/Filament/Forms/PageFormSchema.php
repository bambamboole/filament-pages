<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Filament\Forms;

use Bambamboole\FilamentPages\Facades\FilamentPages;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class PageFormSchema
{
    /**
     * Build the shared page form content schema.
     *
     * @param  int|null  $excludePageId  Page ID to exclude from parent options (prevents self-referencing).
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function make(?int $excludePageId = null): array
    {
        $titleField = TextInput::make('title')->required();

        $leftSchemaFields = [$titleField];

        $leftSchemaFields[] = Builder::make('blocks')
            ->blocks(FilamentPagesPlugin::get()->getBuilderBlocks())
            ->collapsible()
            ->columnSpanFull();

        $slugField = TextInput::make('slug');

        return [
            Grid::make(3)->schema([
                Section::make()->schema($leftSchemaFields)->columnSpan(2),
                Section::make()->schema([
                    $slugField,
                    Select::make('locale')
                        ->options(FilamentPages::locales())
                        ->visible(FilamentPages::hasLocales())
                        ->live()
                        ->afterStateUpdated(fn (Set $set): mixed => $set('parent_id', null)),
                    Select::make('parent_id')
                        ->label('Parent Page')
                        ->options(fn (Get $get, ?Page $record): array => Page::getNestedOptions($excludePageId ?? $record?->id, $get('locale')))
                        ->placeholder('None (Root Page)'),
                    DateTimePicker::make('published_at')
                        ->label('Published At')
                        ->native(false),
                    Select::make('layout')
                        ->label('Layout')
                        ->options(FilamentPagesPlugin::get()->getLayoutOptions())
                        ->placeholder('Default'),
                    Placeholder::make('author')
                        ->label('Author')
                        ->content(fn (?Page $record): string => $record?->author->name ?? 'Unknown')
                        ->visibleOn('edit'),
                ])->columnSpan(1),
            ]),
        ];
    }

    /**
     * Wrap content schema in SEO tabs.
     *
     * @param  array<\Filament\Schemas\Components\Component>  $contentSchema
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function wrapInSeoTabs(array $contentSchema): array
    {
        return [
            Tabs::make('Page')->tabs([
                Tab::make('Content')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema($contentSchema),
                Tab::make('SEO')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema(SeoFormSchema::make()),
            ])->columnSpanFull(),
        ];
    }
}
