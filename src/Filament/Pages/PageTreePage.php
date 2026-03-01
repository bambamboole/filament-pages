<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Pages;

use BackedEnum;
use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PageTreePage extends FilamentPage
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $title = 'Pages';

    protected static ?string $slug = 'page-tree';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-pages::pages.page-tree'),
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function getTreeItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Page::buildTree();
    }

    public function editPageAction(): Action
    {
        return Action::make('editPage')
            ->mountUsing(function (Schema $form, array $arguments): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                $form->fill([
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'parent_id' => $page->parent_id,
                    'content' => $page->content,
                ]);
            })
            ->schema(fn (array $arguments): array => [
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent Page')
                    ->options(fn () => Page::getNestedOptions($arguments['pageId'] ?? null))
                    ->placeholder('None (Root Page)'),
                MarkdownEditor::make('content'),
            ])
            ->action(function (array $data, array $arguments): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                if (empty($data['slug']) && ! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);
                }

                $page->update($data);
            });
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
        return [
            Action::make('createPage')
                ->label('New Page')
                ->schema([
                    TextInput::make('title')
                        ->required(),
                    TextInput::make('slug'),
                    Select::make('parent_id')
                        ->label('Parent Page')
                        ->options(fn () => Page::getNestedOptions())
                        ->placeholder('None (Root Page)'),
                    MarkdownEditor::make('content'),
                ])
                ->action(function (array $data): void {
                    if (empty($data['slug']) && ! empty($data['title'])) {
                        $data['slug'] = Str::slug($data['title']);
                    }

                    Page::create($data);
                }),
            Action::make('tableView')
                ->label('Table View')
                ->icon(Heroicon::OutlinedTableCells)
                ->url(PageResource::getUrl()),
        ];
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
