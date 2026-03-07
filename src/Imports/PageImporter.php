<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Imports;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageImporter
{
    /** @var array<string, int> dir_key => page_id */
    private array $pageCache = [];

    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $pruned = 0;

    /**
     * @return array{ImportResult, Page}
     */
    public function import(PageFile $file, ?int $parentId): array
    {
        $matchKeys = [
            'type' => $file->type,
            'locale' => $file->locale,
            'parent_id' => $parentId,
            'slug' => $file->slug,
        ];

        $updateData = [
            'title' => $file->title,
            'blocks' => $file->blocks ?: null,
            'published_at' => $file->publishedAt,
            'layout' => $file->layout,
        ];

        $existing = Page::withoutGlobalScopes()
            ->withTrashed()
            ->where($matchKeys)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $hasChanges = false;
            foreach ($updateData as $key => $value) {
                $currentValue = $existing->getAttribute($key);

                if ($key === 'published_at') {
                    $currentStr = $currentValue?->toDateTimeString();
                    if ($currentStr !== $value) {
                        $hasChanges = true;
                        break;
                    }
                } elseif ($currentValue !== $value) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges) {
                $existing->updateQuietly($updateData);
                $existing->updateQuietly(['slug_path' => $existing->computeSlugPath()]);

                if ($existing->order !== $file->order) {
                    $existing->updateQuietly(['order' => $file->order]);
                }

                $this->updated++;

                return [ImportResult::Updated, $existing->fresh()];
            }

            $this->unchanged++;

            return [ImportResult::Unchanged, $existing];
        }

        $page = new Page;
        $page->forceFill(array_merge($matchKeys, $updateData));
        $page->slug_path = $page->computeSlugPath();
        $page->saveQuietly();

        if ($page->order !== $file->order) {
            $page->updateQuietly(['order' => $file->order]);
        }

        $this->created++;

        return [ImportResult::Created, $page->fresh()];
    }

    /**
     * @return int|null|false null = root page, false = parent not found
     */
    public function resolveParent(?string $parentDirKey): int|null|false
    {
        if ($parentDirKey === null) {
            return null;
        }

        return $this->pageCache[$parentDirKey] ?? false;
    }

    public function cachePage(?string $dirKey, int $pageId): void
    {
        if ($dirKey !== null) {
            $this->pageCache[$dirKey] = $pageId;
        }
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks
     */
    public function importMedia(Page $page, array $blocks, string $sourceDir): void
    {
        foreach ($blocks as $index => $block) {
            if ($block['type'] !== 'image') {
                continue;
            }
            if (!isset($block['data']['file'])) {
                continue;
            }
            $filePath = $block['data']['file'];

            if (str_starts_with($filePath, './') || !str_starts_with($filePath, '/')) {
                $filePath = $sourceDir.'/'.ltrim((string) $filePath, './');
            }

            if (!file_exists($filePath)) {
                continue;
            }

            $collectionId = 'import-'.Str::slug($page->slug === '/' ? 'home' : $page->slug).'-'.$index;

            $page->clearMediaCollection($collectionId);
            $page->addMedia($filePath)
                ->preservingOriginal()
                ->toMediaCollection($collectionId);

            $updatedBlocks = $page->blocks;
            $updatedBlocks[$index]['data']['image_collection_id'] = $collectionId;
            unset($updatedBlocks[$index]['data']['file']);
            $page->updateQuietly(['blocks' => $updatedBlocks]);
        }
    }

    /**
     * @param  array<string, mixed>  $seo
     */
    public function updateSeo(Page $page, array $seo): void
    {
        if ($seo === []) {
            return;
        }

        $page->seo()->updateOrCreate([], [
            'title' => $seo['title'] ?? null,
            'description' => $seo['description'] ?? null,
            'author' => $seo['author'] ?? null,
        ]);
    }

    /**
     * @param  Collection<int, string>  $importedKeys
     * @return Collection<int, Page>
     */
    public function pruneOrphans(Collection $importedKeys, string $type, ?string $locale): Collection
    {
        $existingPages = Page::withoutGlobalScopes()
            ->where('type', $type)
            ->where('locale', $locale)
            ->orderByDesc(DB::raw('LENGTH(slug_path)'))
            ->get();

        $pruned = collect();

        foreach ($existingPages as $page) {
            $key = $this->buildUniqueKey($type, $locale, $page->parent_id, $page->slug);

            if (!$importedKeys->contains($key)) {
                $page->deleteQuietly();
                $this->pruned++;
                $pruned->push($page);
            }
        }

        return $pruned;
    }

    public function buildUniqueKey(string $type, ?string $locale, ?int $parentId, string $slug): string
    {
        return "{$type}:{$locale}:{$parentId}:{$slug}";
    }
}
