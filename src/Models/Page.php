<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Models;

use Bambamboole\FilamentMenu\Concerns\IsLinkable;
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia, Linkable
{
    use HasFactory;
    use HasSEO;
    use InteractsWithMedia;
    use IsLinkable;
    use SoftDeletes;

    protected static string $pageType = 'page';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og-image')->singleFile();
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            image: $this->getFirstMediaUrl('og-image') ?: null,
        );
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    public function getLink(): string
    {
        return url($this->slug_path);
    }

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function frontendUrl(): ?string
    {
        if (! config('filament-pages.routing.enabled')) {
            return null;
        }

        $locales = config('filament-pages.routing.locales', []);

        if ($this->slug_path === '/') {
            return ! empty($locales)
                ? route('filament-pages.home', ['locale' => $this->locale])
                : route('filament-pages.home');
        }

        $path = ltrim($this->slug_path, '/');

        return ! empty($locales)
            ? route('filament-pages.page', ['locale' => $this->locale, 'path' => $path])
            : route('filament-pages.page', ['path' => $path]);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('type', fn (Builder $query) => $query->where('type', static::$pageType));

        static::saving(function (Page $page) {
            $page->type ??= static::$pageType;
            if (empty($page->slug) && ! empty($page->title)) {
                $page->slug = Str::slug($page->title);
            }

            if (! $page->exists && $page->order === null) {
                $page->order = (static::where('parent_id', $page->parent_id)->max('order') ?? -1) + 1;
            }

            $page->slug_path = $page->computeSlugPath();
        });

        static::saved(function (Page $page) {
            if ($page->wasChanged('slug') || $page->wasChanged('parent_id')) {
                $page->cascadeSlugPath();
            }
        });

        static::deleting(function (Page $page) {
            $page->children()->each(fn (Page $child) => $child->delete());
        });
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Load all pages flat and build a nested collection.
     * Unlike eager-loading children.children.children, this supports unlimited depth.
     *
     * @return Collection<int, self>
     */
    public function getTreeItems(): Collection
    {
        return static::buildTree();
    }

    /**
     * @return Collection<int, self>
     */
    public static function buildTree(?string $locale = null): Collection
    {
        $items = static::query()
            ->where('locale', $locale)
            ->orderBy('order')
            ->get();
        $grouped = $items->groupBy(fn (self $item): int => $item->parent_id ?? 0);

        static::buildTreeRelations($grouped, 0);

        return $grouped->get(0, new Collection);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Collection<int, self>>  $grouped
     */
    private static function buildTreeRelations($grouped, int $parentId): void
    {
        foreach ($grouped->get($parentId, collect()) as $item) {
            $children = $grouped->get($item->id, collect());
            $item->setRelation('children', $children);

            if ($children->isNotEmpty()) {
                static::buildTreeRelations($grouped, $item->id);
            }
        }
    }

    public function computeSlugPath(): string
    {
        if ($this->parent_id === null) {
            return '/' . $this->slug;
        }

        $segments = [$this->slug];
        $currentParentId = $this->parent_id;
        $visited = [];

        while ($currentParentId !== null && ! isset($visited[$currentParentId])) {
            $visited[$currentParentId] = true;

            $ancestor = static::withoutGlobalScopes()
                ->where('type', static::$pageType)
                ->where('id', $currentParentId)
                ->first(['id', 'slug', 'parent_id']);

            if ($ancestor === null) {
                break;
            }

            array_unshift($segments, $ancestor->slug);
            $currentParentId = $ancestor->parent_id;
        }

        return '/' . implode('/', $segments);
    }

    /**
     * Build a flat options array with depth-indented titles for use in select fields.
     *
     * @return array<int, string>
     */
    public static function getNestedOptions(?int $excludeId = null, ?string $locale = null): array
    {
        $tree = static::buildTree($locale);
        $options = [];
        static::flattenTreeOptions($tree, $options, 0, $excludeId);

        return $options;
    }

    /**
     * @param  Collection<int, self>  $items
     * @param  array<int, string>  $options
     */
    private static function flattenTreeOptions(Collection $items, array &$options, int $depth, ?int $excludeId): void
    {
        foreach ($items as $item) {
            if ($item->id === $excludeId) {
                continue;
            }

            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $options[$item->id] = $prefix . $item->title;

            if ($item->children->isNotEmpty()) {
                static::flattenTreeOptions($item->children, $options, $depth + 1, $excludeId);
            }
        }
    }

    /**
     * Render a single block to HTML.
     *
     * @param  array{type: string, data: array<string, mixed>}  $block
     */
    public function renderBlock(array $block): string
    {
        $blockMap = $this->getBlockMap();
        $blockClass = $blockMap[$block['type']] ?? null;

        if (! $blockClass) {
            return '';
        }

        $data = $blockClass::mutateData($block['data'] ?? [], $this);

        return view($blockClass::viewName(), $data)->render();
    }

    /**
     * Render all blocks to HTML.
     */
    public function renderBlocks(): string
    {
        return collect($this->blocks ?? [])
            ->map(fn (array $block): string => $this->renderBlock($block))
            ->implode('');
    }

    /**
     * Build a block type → class map respecting plugin-registered blocks.
     *
     * @return array<string, class-string<PageBlock>>
     */
    private function getBlockMap(): array
    {
        /** @var array<class-string<PageBlock>> $blockClasses */
        $blockClasses = app(\Bambamboole\FilamentPages\FilamentPagesPlugin::class)->getBlockClasses();

        $map = [];
        foreach ($blockClasses as $class) {
            $map[$class::name()] = $class;
        }

        return $map;
    }

    public function cascadeSlugPath(): void
    {
        $parentPath = $this->slug_path;

        $this->children()->each(function (Page $child) use ($parentPath) {
            $child->slug_path = $parentPath . '/' . $child->slug;
            $child->saveQuietly();

            $child->cascadeSlugPath();
        });
    }

    protected static function newFactory(): \Bambamboole\FilamentPages\Database\Factories\PageFactory
    {
        return \Bambamboole\FilamentPages\Database\Factories\PageFactory::new();
    }
}
