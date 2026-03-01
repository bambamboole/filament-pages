<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Pages;

use BackedEnum;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\SelectAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PageTreePage extends FilamentPage
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $title = 'Pages';

    protected static ?string $slug = 'page-tree';

    public ?string $locale = '';

    public function mount(): void
    {
        $plugin = FilamentPagesPlugin::get();

        if ($plugin->hasLocales()) {
            $this->locale = array_key_first($plugin->getLocales());
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-pages::pages.page-tree'),
            ]);
    }

    public function hasLocales(): bool
    {
        return FilamentPagesPlugin::get()->hasLocales();
    }

    /**
     * @return array<string, string>
     */
    public function getLocaleOptions(): array
    {
        return FilamentPagesPlugin::get()->getLocales();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function getTreeItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Page::buildTree($this->locale ?: null);
    }

    public function editPageAction(): Action
    {
        return Action::make('editPage')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->record(fn (array $arguments): ?Page => Page::find($arguments['pageId']))
            ->mountUsing(function (Schema $form, array $arguments): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                $form->fill([
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'locale' => $page->locale,
                    'parent_id' => $page->parent_id,
                    'published_at' => $page->published_at,
                    'layout' => $page->layout,
                    'blocks' => $page->blocks ?? [],
                ]);
            })
            ->schema(fn (array $arguments): array => [
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('locale')
                    ->options(FilamentPagesPlugin::get()->getLocales())
                    ->visible(FilamentPagesPlugin::get()->hasLocales())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('parent_id', null)),
                Select::make('parent_id')
                    ->label('Parent Page')
                    ->options(fn (Get $get) => Page::getNestedOptions($arguments['pageId'] ?? null, $get('locale')))
                    ->placeholder('None (Root Page)'),
                DateTimePicker::make('published_at')
                    ->label('Published At')
                    ->native(false),
                Select::make('layout')
                    ->label('Layout')
                    ->options(FilamentPagesPlugin::get()->getLayoutOptions())
                    ->placeholder('Default'),
                Builder::make('blocks')
                    ->blocks(FilamentPagesPlugin::get()->getBuilderBlocks())
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, array $arguments, Schema $form): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                if (empty($data['slug']) && ! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);
                }

                $page->update($data);
                $form->model($page)->saveRelationships();
            })
            ->extraModalFooterActions(fn (array $arguments): array => [
                Action::make('visitPage')
                    ->label('Visit Page')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (): ?string => Page::find($arguments['pageId'])?->frontendUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (): bool => (bool) Page::find($arguments['pageId'])?->isPublished()),
            ]);
    }

    public function deletePageAction(): Action
    {
        return Action::make('deletePage')
            ->requiresConfirmation()
            ->color('danger')
            ->action(function (array $arguments): void {
                $page = Page::find($arguments['pageId']);
                $page?->delete();
            });
    }

    public function updatePublishedAtAction(): Action
    {
        return Action::make('updatePublishedAt')
            ->modalHeading('Set Publication Date')
            ->modalWidth(Width::Medium)
            ->mountUsing(function (Schema $form, array $arguments): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                $form->fill([
                    'published_at' => $page->published_at,
                ]);
            })
            ->schema([
                DateTimePicker::make('published_at')
                    ->label('Published At')
                    ->native(false),
            ])
            ->action(function (array $data, array $arguments): void {
                $page = Page::find($arguments['pageId']);
                $page?->update(['published_at' => $data['published_at']]);
            });
    }

    /**
     * @return array<Action>
     */
    public function getExtraTreeItemActions(): array
    {
        $actions = [];

        foreach (FilamentPagesPlugin::get()->getTreeItemActionCallbacks() as $callback) {
            $actions = array_merge($actions, $callback($this));
        }

        return $actions;
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $tree
     */
    public function reorderTree(array $tree): void
    {
        $order = 0;
        $this->persistTree($tree, null, $order);

        // Recompute slug_paths for all pages after reorder
        Page::query()->orderBy('order')->each(function (Page $page) {
            $newSlugPath = $page->computeSlugPath();
            if ($page->slug_path !== $newSlugPath) {
                $page->slug_path = $newSlugPath;
                $page->saveQuietly();
            }
        });
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (FilamentPagesPlugin::get()->hasLocales()) {
            $actions[] = SelectAction::make('locale')
                ->label('Locale')
                ->options(['' => 'No Locale'] + FilamentPagesPlugin::get()->getLocales());
        }

        $actions[] = Action::make('createPage')
            ->label('New Page')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm([
                'locale' => $this->locale,
            ])
            ->schema([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
                Select::make('locale')
                    ->options(FilamentPagesPlugin::get()->getLocales())
                    ->visible(FilamentPagesPlugin::get()->hasLocales())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('parent_id', null)),
                Select::make('parent_id')
                    ->label('Parent Page')
                    ->options(fn (Get $get) => Page::getNestedOptions(locale: $get('locale')))
                    ->placeholder('None (Root Page)'),
                DateTimePicker::make('published_at')
                    ->label('Published At')
                    ->native(false),
                Select::make('layout')
                    ->label('Layout')
                    ->options(FilamentPagesPlugin::get()->getLayoutOptions())
                    ->placeholder('Default'),
                Builder::make('blocks')
                    ->blocks(FilamentPagesPlugin::get()->getBuilderBlocks())
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, Schema $form): void {
                if (empty($data['slug']) && ! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);
                }

                $page = Page::create($data);
                $form->model($page)->saveRelationships();
            });

        $actions[] = Action::make('tableView')
            ->label('Table View')
            ->icon(Heroicon::OutlinedTableCells)
            ->url(FilamentPagesPlugin::get()->getResource()::getUrl());

        return $actions;
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $items
     */
    private function persistTree(array $items, ?int $parentId, int &$order): void
    {
        foreach ($items as $item) {
            Page::where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'order' => $order++,
            ]);

            if (! empty($item['children'])) {
                $this->persistTree($item['children'], $item['id'], $order);
            }
        }
    }
}
