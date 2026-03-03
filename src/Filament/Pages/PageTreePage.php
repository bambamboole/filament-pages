<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Filament\Pages;

use BackedEnum;
use Bambamboole\FilamentPages\Facades\FilamentPages;
use Bambamboole\FilamentPages\Filament\Forms\PageFormSchema;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\SelectAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Pboivin\FilamentPeek\Facades\Peek;
use Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal;

/**
 * @property-read Schema $pageForm
 */
class PageTreePage extends FilamentPage
{
    use HasPreviewModal;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $title = 'Pages';

    protected static ?string $slug = 'page-tree';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?string $locale = '';

    #[Url(as: 'editPage')]
    public ?int $editPageId = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $formMode = null;

    public ?int $activePageId = null;

    public function mount(): void
    {
        if (FilamentPages::hasLocales()) {
            $this->locale = array_key_first(FilamentPages::locales());
        }

        Peek::registerPreviewModal();

        if ($this->editPageId !== null) {
            $this->selectPage($this->editPageId);
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

    public function defaultPageForm(Schema $schema): Schema
    {
        $record = $this->activePageId ? $this->getPageModel()::find($this->activePageId) : null;

        return $schema
            ->model($record ?? $this->getPageModel())
            ->operation($this->formMode === 'edit' ? 'edit' : 'create')
            ->statePath('data');
    }

    public function pageForm(Schema $schema): Schema
    {
        return $schema->components(
            PageFormSchema::wrapInSeoTabs(
                PageFormSchema::make(excludePageId: $this->activePageId),
            )
        );
    }

    #[\Override]
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-pages::pages.page-tree-layout'),
            ]);
    }

    public function selectPage(int $pageId): void
    {
        $record = $this->getPageModel()::find($pageId);

        if (!$record instanceof Page) {
            return;
        }

        if (!$this->authorizePageAction('update', $record)) {
            return;
        }

        $this->formMode = 'edit';
        $this->activePageId = $pageId;
        $this->editPageId = $pageId;

        $this->cacheSchema('pageForm');

        $this->getSchema('pageForm')->fill([
            'title' => $record->title,
            'slug' => $record->slug,
            'locale' => $record->locale,
            'parent_id' => $record->parent_id,
            'published_at' => $record->published_at,
            'layout' => $record->layout,
            'blocks' => $record->blocks ?? [],
        ]);
    }

    public function startCreatePage(): void
    {
        if (!$this->authorizePageAction('create', $this->getPageModel())) {
            return;
        }

        $this->formMode = 'create';
        $this->activePageId = null;
        $this->editPageId = null;

        $this->cacheSchema('pageForm');

        $this->getSchema('pageForm')->fill([
            'locale' => $this->locale,
        ]);
    }

    public function deselectPage(): void
    {
        $this->formMode = null;
        $this->activePageId = null;
        $this->editPageId = null;
        $this->data = [];
    }

    public function savePage(): void
    {
        if ($this->formMode === 'edit') {
            $this->saveEditPage();
        } elseif ($this->formMode === 'create') {
            $this->saveCreatePage();
        }
    }

    public function previewPage(): void
    {
        $data = $this->pageForm->getState();
        $model = $this->getPageModel();

        if ($this->formMode === 'edit' && $this->activePageId) {
            $record = $model::find($this->activePageId);
            if ($record) {
                $previewPage = $record->replicate();
                $previewPage->fill($data);
            }
        } else {
            $previewPage = new $model($data);
        }

        if (isset($previewPage)) {
            $this->setPreviewableRecord($previewPage);
            $this->openPreviewModal();
        }
    }

    public function hasLocales(): bool
    {
        return FilamentPages::hasLocales();
    }

    /**
     * @return array<string, string>
     */
    public function getLocaleOptions(): array
    {
        return FilamentPages::locales();
    }

    /**
     * @return class-string<Page>
     */
    protected function getPageModel(): string
    {
        return FilamentPages::model();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function getTreeItems(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getPageModel()::buildTree($this->locale ?: null);
    }

    public function deletePageAction(): Action
    {
        return Action::make('deletePage')
            ->requiresConfirmation()
            ->color('danger')
            ->record(fn (array $arguments): ?Page => $this->getPageModel()::find($arguments['pageId']))
            ->visible(fn (?Page $record): bool => $record
                && !$record->children()->exists()
                && $this->authorizePageAction('delete', $record))
            ->action(function (?Page $record): void {
                if (!$record instanceof \Bambamboole\FilamentPages\Models\Page) {
                    return;
                }

                $this->authorizePageAction('delete', $record, enforce: true);
                $record->delete();

                if ($this->activePageId === $record->id) {
                    $this->deselectPage();
                }

                Notification::make()
                    ->title('Page deleted')
                    ->success()
                    ->send();
            });
    }

    public function updatePublishedAtAction(): Action
    {
        return Action::make('updatePublishedAt')
            ->modalHeading('Set Publication Date')
            ->modalWidth(Width::Medium)
            ->record(fn (array $arguments): ?Page => $this->getPageModel()::find($arguments['pageId']))
            ->visible(fn (?Page $record): bool => $this->authorizePageAction('update', $record))
            ->mountUsing(function (Schema $form, ?Page $record): void {
                if (!$record instanceof \Bambamboole\FilamentPages\Models\Page) {
                    return;
                }

                $form->fill([
                    'published_at' => $record->published_at,
                ]);
            })
            ->schema([
                DateTimePicker::make('published_at')
                    ->label('Published At')
                    ->native(false),
            ])
            ->action(function (array $data, ?Page $record): void {
                if (!$record instanceof \Bambamboole\FilamentPages\Models\Page) {
                    return;
                }

                $this->authorizePageAction('update', $record, enforce: true);
                $record->update(['published_at' => $data['published_at']]);

                Notification::make()
                    ->title('Publication date updated')
                    ->success()
                    ->send();
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
        $model = $this->getPageModel();

        if (Gate::getPolicyFor($model)) {
            Gate::authorize('reorder', $model);
        }

        $homepageId = $model::query()
            ->where('locale', $this->locale ?: null)
            ->where('slug', '/')
            ->value('id');

        if ($homepageId !== null) {
            $homepageNode = collect($tree)->firstWhere('id', $homepageId);
            if ($homepageNode && !empty($homepageNode['children'])) {
                Notification::make()
                    ->title('Cannot nest pages under the homepage')
                    ->danger()
                    ->send();

                return;
            }
        }

        $order = 0;
        $this->persistTree($tree, null, $order);

        // Recompute slug_paths for affected pages after reorder
        $model::query()
            ->where('locale', $this->locale ?: null)
            ->orderBy('order')
            ->each(function (Page $page): void {
                $newSlugPath = $page->computeSlugPath();
                if ($page->slug_path !== $newSlugPath) {
                    $page->slug_path = $newSlugPath;
                    $page->saveQuietly();
                }
            });

        Notification::make()
            ->title('Page order updated')
            ->success()
            ->send();
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (FilamentPages::hasLocales()) {
            $actions[] = SelectAction::make('locale')
                ->label('Locale')
                ->options(['' => 'No Locale'] + FilamentPages::locales());
        }

        $actions[] = Action::make('createPage')
            ->label('New Page')
            ->visible(fn (): bool => $this->authorizePageAction('create', $this->getPageModel()))
            ->action(fn () => $this->startCreatePage());

        return $actions;
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $items
     */
    private function persistTree(array $items, ?int $parentId, int &$order): void
    {
        $model = $this->getPageModel();

        foreach ($items as $item) {
            $model::where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'order' => $order++,
            ]);

            if (!empty($item['children'])) {
                $this->persistTree($item['children'], $item['id'], $order);
            }
        }
    }

    /**
     * Check authorization for a page action. Returns true when no policy is registered.
     * When enforce is true, throws AuthorizationException instead of returning false.
     */
    private function authorizePageAction(string $ability, Page|string $target, bool $enforce = false): bool
    {
        if (!Gate::getPolicyFor($this->getPageModel())) {
            return true;
        }

        if ($enforce) {
            Gate::authorize($ability, $target);

            return true;
        }

        return Gate::allows($ability, $target);
    }

    private function saveEditPage(): void
    {
        $record = $this->getPageModel()::find($this->activePageId);

        if (!$record instanceof Page) {
            return;
        }

        $this->authorizePageAction('update', $record, enforce: true);

        $data = $this->pageForm->getState();

        if (($data['slug'] ?? null) === null && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if (($data['slug'] ?? null) === '/' && $record->children()->exists()) {
            Notification::make()
                ->title('Cannot set slug to "/" because this page has children')
                ->danger()
                ->send();

            return;
        }

        $record->update($data);
        $this->pageForm->model($record)->saveRelationships();

        Notification::make()
            ->title('Page updated')
            ->success()
            ->send();
    }

    private function saveCreatePage(): void
    {
        $model = $this->getPageModel();
        $this->authorizePageAction('create', $model, enforce: true);

        $data = $this->pageForm->getState();

        if (($data['slug'] ?? null) === null && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $page = $model::create($data);
        $this->pageForm->model($page)->saveRelationships();

        Notification::make()
            ->title('Page created')
            ->success()
            ->send();

        $this->selectPage($page->id);
    }
}
