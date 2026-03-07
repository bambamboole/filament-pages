<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Imports;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

final readonly class PageFile
{
    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks
     * @param  array<string, mixed>  $seo
     */
    public function __construct(
        public string $title,
        public string $slug,
        public int $order,
        public ?string $publishedAt,
        public ?string $layout,
        public ?string $locale,
        public string $type,
        public array $blocks,
        public array $seo,
        public ?string $dirKey,
        public ?string $parentDirKey,
        public string $sourceDir,
    ) {}

    /**
     * @return static|null Returns null for invalid/untitled files.
     */
    public static function fromYaml(SplFileInfo $file, ?string $locale, string $type): ?self
    {
        $data = Yaml::parseFile($file->getRealPath());

        if (!is_array($data)) {
            return null;
        }

        $title = $data['title'] ?? null;
        if ($title === null) {
            return null;
        }

        $filename = $file->getFilenameWithoutExtension();
        $isIndex = $filename === '_index';
        $relativePath = $file->getRelativePath();

        $slug = self::resolveSlug($filename, $data['slug'] ?? null);
        $order = self::resolveOrder($filename, $data['order'] ?? null);
        $blocks = self::normalizeBlocks($data['blocks'] ?? []);

        if ($isIndex) {
            $dirKey = $relativePath ?: null;
            $parentDirKey = str_contains($relativePath, '/') ? dirname($relativePath) : null;

            if ($slug === '' && $relativePath !== '') {
                $dirName = basename($relativePath);
                $slug = Str::slug(preg_replace('/^\d+-/', '', $dirName));
            }
        } else {
            $dirKey = $relativePath !== '' ? $relativePath.'/'.$slug : $slug;
            $parentDirKey = $relativePath !== '' ? $relativePath : null;
        }

        return new self(
            title: $title,
            slug: $slug,
            order: $order,
            publishedAt: $data['published_at'] ?? now()->toDateTimeString(),
            layout: $data['layout'] ?? null,
            locale: $data['locale'] ?? $locale,
            type: $type,
            blocks: $blocks,
            seo: $data['seo'] ?? [],
            dirKey: $dirKey,
            parentDirKey: $parentDirKey,
            sourceDir: dirname($file->getRealPath()),
        );
    }

    public static function fromPage(Page $page, string $sourceDir): self
    {
        $seo = [];
        if ($page->relationLoaded('seo') && $page->seo !== null) {
            foreach (['title', 'description', 'author'] as $field) {
                if ($page->seo->{$field} !== null) {
                    $seo[$field] = $page->seo->{$field};
                }
            }
        }

        return new self(
            title: $page->title,
            slug: $page->slug,
            order: $page->order,
            publishedAt: $page->published_at?->toDateTimeString(),
            layout: $page->layout,
            locale: $page->locale,
            type: $page->type,
            blocks: $page->blocks ?? [],
            seo: $seo,
            dirKey: null,
            parentDirKey: null,
            sourceDir: $sourceDir,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toYamlArray(): array
    {
        $data = ['title' => $this->title];
        $data['slug'] = $this->slug;

        if ($this->order > 0) {
            $data['order'] = $this->order;
        }

        if ($this->publishedAt !== null) {
            $data['published_at'] = $this->publishedAt;
        }

        if ($this->layout !== null) {
            $data['layout'] = $this->layout;
        }

        if (!empty($this->blocks)) {
            $data['blocks'] = self::denormalizeBlocks($this->blocks);
        }

        if (!empty($this->seo)) {
            $data['seo'] = $this->seo;
        }

        return $data;
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function denormalizeBlocks(array $blocks): array
    {
        return array_map(
            fn (array $b): array => array_merge(['type' => $b['type']], $b['data'] ?? []),
            $blocks
        );
    }

    private static function resolveSlug(string $filename, ?string $metaSlug): string
    {
        if ($metaSlug !== null) {
            return $metaSlug === '/' ? '/' : ltrim($metaSlug, '/');
        }

        if ($filename === '_index') {
            return '';
        }

        $cleaned = preg_replace('/^\d+-/', '', $filename);

        return Str::slug($cleaned);
    }

    private static function resolveOrder(string $filename, ?int $metaOrder): int
    {
        if ($metaOrder !== null) {
            return $metaOrder;
        }

        if (preg_match('/^(\d+)-/', $filename, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private static function normalizeBlocks(array $blocks): array
    {
        return array_values(array_map(function (array $block): array {
            $type = $block['type'] ?? 'unknown';
            $data = $block;
            unset($data['type']);

            return ['type' => $type, 'data' => $data];
        }, $blocks));
    }
}
