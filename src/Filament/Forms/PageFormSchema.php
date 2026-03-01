<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Forms;

use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PageFormSchema
{
    /**
     * Build the shared page form content schema.
     *
     * @param  bool  $withSlugSync  Enable live title-to-slug synchronization (used in resource form).
     * @param  int|null  $excludePageId  Page ID to exclude from parent options (prevents self-referencing).
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function make(bool $withSlugSync = false, ?int $excludePageId = null): array
    {
        $plugin = FilamentPagesPlugin::get();

        $titleField = TextInput::make('title')->required();

        if ($withSlugSync) {
            $titleField = $titleField
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                    if (! $get('is_slug_changed_manually') && filled($state)) {
                        $set('slug', Str::slug($state));
                    }
                })
                ->live(debounce: 300);
        }

        $leftSchemaFields = [$titleField];

        if ($withSlugSync) {
            $leftSchemaFields[] = Hidden::make('is_slug_changed_manually')
                ->default(false)
                ->dehydrated(false);
        }

        $leftSchemaFields[] = Builder::make('blocks')
            ->blocks($plugin->getBuilderBlocks())
            ->collapsible()
            ->columnSpanFull();

        $slugField = TextInput::make('slug');

        if ($withSlugSync) {
            $slugField = $slugField->afterStateUpdated(function (Set $set) {
                $set('is_slug_changed_manually', true);
            });
        }

        if ($withSlugSync) {
            $slugField = $slugField->required();
        }

        $contentSchema = [
            Grid::make(3)->schema([
                Section::make()->schema($leftSchemaFields)->columnSpan(2),
                Section::make()->schema([
                    $slugField,
                    Select::make('locale')
                        ->options($plugin->getLocales())
                        ->visible($plugin->hasLocales())
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('parent_id', null)),
                    Select::make('parent_id')
                        ->label('Parent Page')
                        ->options(fn (Get $get, ?Page $record) => Page::getNestedOptions($excludePageId ?? $record?->id, $get('locale')))
                        ->placeholder('None (Root Page)'),
                    DateTimePicker::make('published_at')
                        ->label('Published At')
                        ->native(false),
                    Select::make('layout')
                        ->label('Layout')
                        ->options($plugin->getLayoutOptions())
                        ->placeholder('Default'),
                ])->columnSpan(1),
            ]),
        ];

        return $contentSchema;
    }

    /**
     * Wrap content schema in SEO tabs if SEO is enabled.
     *
     * @param  array<\Filament\Schemas\Components\Component>  $contentSchema
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function wrapInSeoTabs(array $contentSchema): array
    {
        if (! FilamentPagesPlugin::get()->isSeoEnabled()) {
            return $contentSchema;
        }

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
