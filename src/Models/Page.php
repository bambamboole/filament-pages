<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Models;

use Bambamboole\FilamentMenu\Concerns\IsLinkable;
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Facades\FilamentPages;
use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $title
 * @property ?string $slug
 * @property string $slug_path
 * @property string $type
 * @property ?int $parent_id
 * @property ?string $locale
 * @property int $order
 * @property ?CarbonInterface $published_at
 * @property ?string $layout
 * @property ?int $author_id
 * @property ?array<int, array{type: string, data: array<string, mixed>}> $blocks
 */
class Page extends Model implements HasMedia, Linkable
{
    use HasFactory;
    use HasSEO;
    use InteractsWithMedia;
    use IsLinkable;
    use SoftDeletes;

    protected static string $pageType = 'page';

    protected $guarded = [];

    #[\Override]
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
        $ogImageUrl = $this->getFirstMediaUrl('og-image') ?: null;

        if (!$ogImageUrl) {
            $defaultPath = FilamentPages::seoDefaults()['default_og_image'];

            if ($defaultPath) {
                $ogImageUrl = asset($defaultPath);
            }
        }

        return new SEOData(
            image: $ogImageUrl,
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
        return url($this->localePrefixedPath());
    }

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function frontendUrl(): string
    {
        return url($this->localePrefixedPath());
    }

    /**
     * Build the URL path including locale prefix when locales are configured.
     */
    private function localePrefixedPath(): string
    {
        $service = app(FilamentPagesService::class);

        if ($service->hasLocales() && $this->locale !== null) {
            return '/'.$this->locale.rtrim($this->slug_path, '/');
        }

        return $this->slug_path;
    }

    #[\Override]
    protected static function booted(): void
    {
        static::addGlobalScope('type', fn (Builder $query) => $query->where('type', static::$pageType));

        static::creating(function (Page $page): void {
            $page->author_id ??= auth()->id();
        });

        static::saving(function (Page $page): void {
            $page->type ??= static::$pageType;
            if ($page->slug === null) {
                $page->slug = Str::slug($page->title);
            }

            if ($page->slug === '/' && $page->exists && $page->children()->exists()) {
                throw ValidationException::withMessages([
                    'slug' => 'Cannot set slug to "/" because this page has children.',
                ]);
            }

            if (!$page->exists) {
                $page->order = (static::where('parent_id', $page->parent_id)->max('order') ?? -1) + 1;
            }

            $page->slug_path = $page->computeSlugPath();
        });

        static::saved(function (Page $page): void {
            if ($page->wasChanged('slug') || $page->wasChanged('parent_id')) {
                $page->cascadeSlugPath();
            }
        });

        static::deleting(function (Page $page): void {
            if ($page->children()->exists()) {
                throw new \LogicException("Cannot delete page [{$page->id}] because it has child pages. Move or delete children first.");
            }
        });
    }

    /** @return BelongsTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'author_id');
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
     * @return Collection<int, static>
     */
    public function getTreeItems(): Collection
    {
        return static::buildTree();
    }

    /**
     * @return Collection<int, static>
     */
    public static function buildTree(?string $locale = null): Collection
    {
        $items = static::query()
            ->where('locale', $locale)
            ->orderBy('order')
            ->get();
        $grouped = $items->groupBy(fn (self $item): int => $item->parent_id ?? 0);

        self::buildTreeRelations($grouped, 0);

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
                self::buildTreeRelations($grouped, $item->id);
            }
        }
    }

    public function computeSlugPath(): string
    {
        if ($this->slug === '/') {
            return '/';
        }

        if ($this->parent_id === null) {
            return '/'.$this->slug;
        }

        $segments = [$this->slug];
        $currentParentId = $this->parent_id;
        $visited = [];

        while ($currentParentId !== null && !isset($visited[$currentParentId])) {
            $visited[$currentParentId] = true;

            $ancestor = static::withoutGlobalScopes()
                ->where('type', static::$pageType)
                ->where('id', $currentParentId)
                ->first(['id', 'slug', 'parent_id']);

            if ($ancestor === null) {
                break;
            }

            // Skip the homepage slug — it's "/" and would corrupt the path
            if ($ancestor->slug !== '/') {
                array_unshift($segments, $ancestor->slug);
            }

            $currentParentId = $ancestor->parent_id;
        }

        return '/'.implode('/', $segments);
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
        self::flattenTreeOptions($tree, $options, 0, $excludeId);

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
            if ($item->slug === '/') {
                continue;
            }
            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $options[$item->id] = $prefix.$item->title;

            if ($item->children->isNotEmpty()) {
                self::flattenTreeOptions($item->children, $options, $depth + 1, $excludeId);
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

        if (!$blockClass) {
            return '';
        }

        $data = $blockClass::mutateData($block['data'], $this);

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
        $blockClasses = FilamentPages::blockClasses();

        $map = [];
        foreach ($blockClasses as $class) {
            $map[$class::name()] = $class;
        }

        return $map;
    }

    public function cascadeSlugPath(): void
    {
        $parentPath = $this->slug_path;

        $this->children()->each(function (Page $child) use ($parentPath): void {
            // When parent is homepage (slug_path="/"), avoid double slashes
            $child->slug_path = rtrim($parentPath, '/').'/'.$child->slug;
            $child->saveQuietly();

            $child->cascadeSlugPath();
        });
    }

    protected static function newFactory(): \Bambamboole\FilamentPages\Database\Factories\PageFactory
    {
        return \Bambamboole\FilamentPages\Database\Factories\PageFactory::new();
    }
}
