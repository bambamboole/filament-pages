<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Pages;

use BackedEnum;
use Bambamboole\FilamentPages\Filament\Forms\PageFormSchema;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\SelectAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Pboivin\FilamentPeek\Facades\Peek;
use Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal;

class PageTreePage extends FilamentPage
{
    use HasPreviewModal;

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

        if ($plugin->isPreviewEnabled()) {
            Peek::registerPreviewModal();
        }
    }

    protected function getPreviewModalView(): ?string
    {
        return FilamentPagesPlugin::get()->getPreviewView();
    }

    protected function getPreviewModalDataRecordKey(): string
    {
        return 'page';
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
            ->modalWidth(Width::SevenExtraLarge)
            ->record(fn (array $arguments): ?Page => Page::find($arguments['pageId']))
            ->visible(fn (?Page $record): bool => $this->authorizePageAction('update', $record))
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
            ->schema(fn (array $arguments): array => PageFormSchema::wrapInSeoTabs(
                PageFormSchema::make(excludePageId: $arguments['pageId'] ?? null),
            ))
            ->action(function (array $data, array $arguments, Schema $form): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                $this->authorizePageAction('update', $page, enforce: true);

                if (empty($data['slug']) && ! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);
                }

                $page->update($data);
                $form->model($page)->saveRelationships();
            })
            ->extraModalFooterActions(fn (array $arguments): array => array_filter([
                FilamentPagesPlugin::get()->isPreviewEnabled()
                    ? Action::make('previewEditPage')
                        ->label(__('filament-peek::ui.preview-action-label'))
                        ->color('gray')
                        ->action(function (array $data) use ($arguments): void {
                            $page = Page::find($arguments['pageId']);

                            if (! $page) {
                                return;
                            }

                            $previewPage = $page->replicate();
                            $previewPage->fill($data);

                            $this->setPreviewableRecord($previewPage);
                            $this->openPreviewModal();
                        })
                    : null,
                Action::make('visitPage')
                    ->label('Visit Page')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (): ?string => Page::find($arguments['pageId'])?->frontendUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (): bool => (bool) Page::find($arguments['pageId'])?->isPublished()),
            ]));
    }

    public function deletePageAction(): Action
    {
        return Action::make('deletePage')
            ->requiresConfirmation()
            ->color('danger')
            ->record(fn (array $arguments): ?Page => Page::find($arguments['pageId']))
            ->visible(fn (?Page $record): bool => $this->authorizePageAction('delete', $record))
            ->action(function (array $arguments): void {
                $page = Page::find($arguments['pageId']);

                if (! $page) {
                    return;
                }

                $this->authorizePageAction('delete', $page, enforce: true);
                $page->delete();
            });
    }

    public function updatePublishedAtAction(): Action
    {
        return Action::make('updatePublishedAt')
            ->modalHeading('Set Publication Date')
            ->modalWidth(Width::Medium)
            ->record(fn (array $arguments): ?Page => Page::find($arguments['pageId']))
            ->visible(fn (?Page $record): bool => $this->authorizePageAction('update', $record))
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

                if (! $page) {
                    return;
                }

                $this->authorizePageAction('update', $page, enforce: true);
                $page->update(['published_at' => $data['published_at']]);
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
        if (Gate::getPolicyFor(Page::class)) {
            Gate::authorize('reorder', Page::class);
        }

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
            ->modalWidth(Width::SevenExtraLarge)
            ->visible(fn (): bool => $this->authorizePageAction('create', Page::class))
            ->fillForm([
                'locale' => $this->locale,
            ])
            ->schema(PageFormSchema::wrapInSeoTabs(
                PageFormSchema::make(),
            ))
            ->action(function (array $data, Schema $form): void {
                $this->authorizePageAction('create', Page::class, enforce: true);

                if (empty($data['slug']) && ! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);
                }

                $page = Page::create($data);
                $form->model($page)->saveRelationships();
            })
            ->extraModalFooterActions(fn (): array => array_filter([
                FilamentPagesPlugin::get()->isPreviewEnabled()
                    ? Action::make('previewCreatePage')
                        ->label(__('filament-peek::ui.preview-action-label'))
                        ->color('gray')
                        ->action(function (array $data): void {
                            $previewPage = new Page($data);

                            $this->setPreviewableRecord($previewPage);
                            $this->openPreviewModal();
                        })
                    : null,
            ]));

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

    /**
     * Check authorization for a page action. Returns true when no policy is registered.
     * When enforce is true, throws AuthorizationException instead of returning false.
     */
    private function authorizePageAction(string $ability, Page | string $target, bool $enforce = false): bool
    {
        if (! Gate::getPolicyFor(Page::class)) {
            return true;
        }

        if ($enforce) {
            Gate::authorize($ability, $target);

            return true;
        }

        return Gate::allows($ability, $target);
    }
}
