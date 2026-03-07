<?php
declare(strict_types=1);
namespace Bambamboole\FilamentPages\Exports;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class PageExporter
{
    public int $exported = 0;

    public int $mediaExported = 0;

    /**
     * @return Collection<int, Page>
     */
    public function queryPages(string $type, ?string $locale): Collection
    {
        return Page::withoutGlobalScopes()
            ->with(['seo', 'children'])
            ->where('type', $type)
            ->when($locale !== null, fn ($q) => $q->where('locale', $locale))
            ->whereNull('deleted_at')
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return Collection<int, array{page: Page, hasChildren: bool, depth: int}>
     */
    public function buildExportOrder(Collection $pages): Collection
    {
        $grouped = $pages->groupBy(fn (Page $p): int => $p->parent_id ?? 0);
        $result = collect();

        $this->traverseDepthFirst($grouped, 0, 0, $result);

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Collection<int, Page>>  $grouped
     * @param  Collection<int, array{page: Page, hasChildren: bool, depth: int}>  $result
     */
    private function traverseDepthFirst(Collection $grouped, int $parentId, int $depth, Collection $result): void
    {
        foreach ($grouped->get($parentId, collect()) as $page) {
            $hasChildren = $grouped->has($page->id);
            $result->push([
                'page' => $page,
                'hasChildren' => $hasChildren,
                'depth' => $depth,
            ]);

            if ($hasChildren) {
                $this->traverseDepthFirst($grouped, $page->id, $depth + 1, $result);
            }
        }
    }

    public function resolveFilePath(Page $page, bool $hasChildren): string
    {
        $orderPrefix = $page->order > 0 ? sprintf('%02d-', $page->order) : '';

        if ($page->slug === '/') {
            return $hasChildren ? '_index.yaml' : 'home.yaml';
        }

        if ($page->parent_id === null) {
            if ($hasChildren) {
                return $orderPrefix.$page->slug.'/_index.yaml';
            }

            return $orderPrefix.$page->slug.'.yaml';
        }

        $segments = explode('/', ltrim($page->slug_path, '/'));
        $filename = array_pop($segments);
        $parentPath = implode('/', $segments);

        if ($hasChildren) {
            return $parentPath.'/'.$orderPrefix.$filename.'/_index.yaml';
        }

        return $parentPath.'/'.$orderPrefix.$filename.'.yaml';
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks
     */
    public function exportMedia(Page $page, array &$blocks, string $exportDir): void
    {
        foreach ($blocks as $index => &$block) {
            if ($block['type'] !== 'image' || !isset($block['data']['image_collection_id'])) {
                continue;
            }

            $collectionId = $block['data']['image_collection_id'];
            $media = $page->getFirstMedia($collectionId);

            if ($media === null) {
                continue;
            }

            $sourcePath = $media->getPath();
            if (!file_exists($sourcePath)) {
                continue;
            }

            $destPath = $exportDir.'/'.$media->file_name;
            File::ensureDirectoryExists(dirname($destPath));
            File::copy($sourcePath, $destPath);

            unset($block['data']['image_collection_id']);
            $block['data']['file'] = './'.$media->file_name;

            $this->mediaExported++;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function writeYaml(string $filePath, array $data): void
    {
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $this->exported++;
    }
}
