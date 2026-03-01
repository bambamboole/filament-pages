<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Resources;

use BackedEnum;
use Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('title')
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            if (! $get('is_slug_changed_manually') && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->live(debounce: 300)
                        ->required(),
                    TextInput::make('slug')
                        ->afterStateUpdated(function (Set $set) {
                            $set('is_slug_changed_manually', true);
                        })
                        ->required(),
                    Hidden::make('is_slug_changed_manually')
                        ->default(false)
                        ->dehydrated(false),
                    Select::make('parent_id')
                        ->label('Parent Page')
                        ->options(fn (?Page $record) => Page::getNestedOptions($record?->id))
                        ->placeholder('None (Root Page)'),
                    MarkdownEditor::make('content'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug_path')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
