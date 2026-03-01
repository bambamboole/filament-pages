<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
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
    public static function buildTree(): Collection
    {
        $items = static::query()->orderBy('order')->get();
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
        $segments = [$this->slug];

        $parent = $this->parent_id !== null
            ? static::find($this->parent_id)
            : null;

        while ($parent) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent_id !== null
                ? static::find($parent->parent_id)
                : null;
        }

        return '/' . implode('/', $segments);
    }

    /**
     * Build a flat options array with depth-indented titles for use in select fields.
     *
     * @return array<int, string>
     */
    public static function getNestedOptions(?int $excludeId = null): array
    {
        $tree = static::buildTree();
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

    public function cascadeSlugPath(): void
    {
        $this->children()->each(function (Page $child) {
            $child->slug_path = $child->computeSlugPath();
            $child->saveQuietly();

            $child->cascadeSlugPath();
        });
    }

    protected static function newFactory(): \Bambamboole\FilamentPages\Database\Factories\PageFactory
    {
        return \Bambamboole\FilamentPages\Database\Factories\PageFactory::new();
    }
}
