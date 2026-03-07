<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Commands;

use Bambamboole\FilamentPages\Imports\ImportResult;
use Bambamboole\FilamentPages\Imports\PageFile;
use Bambamboole\FilamentPages\Imports\PageImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class ImportPagesCommand extends Command
{
    public $signature = 'filament-pages:import
        {--path= : Source directory (default: resources/pages)}
        {--locale= : Locale for imported pages}
        {--type=page : Page type}
        {--prune : Soft-delete DB pages not found in source}
        {--dry-run : Preview changes without modifying DB}';

    public $description = 'Import pages from YAML files into the database (idempotent)';

    private int $skipped = 0;

    public function handle(PageImporter $importer): int
    {
        $basePath = $this->option('path') ?? resource_path('pages');
        $locale = $this->option('locale');
        $type = $this->option('type');
        $isDryRun = (bool) $this->option('dry-run');
        $shouldPrune = (bool) $this->option('prune');

        if (!is_dir($basePath)) {
            $this->components->error("Directory not found: {$basePath}");

            return self::FAILURE;
        }

        $this->components->info("Importing pages from: {$basePath}");
        $this->components->twoColumnDetail('Locale', $locale ?: '(none)');
        $this->components->twoColumnDetail('Type', $type);

        if ($isDryRun) {
            $this->components->warn('Dry run — no changes will be persisted.');
        }

        $this->newLine();

        $files = $this->discoverFiles($basePath);

        if ($files->isEmpty()) {
            $this->components->warn('No YAML files found.');

            return self::SUCCESS;
        }

        /** @var Collection<int, string> $importedKeys */
        $importedKeys = collect();

        if ($isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($files as $file) {
                $pageFile = PageFile::fromYaml($file, $locale, $type);

                if ($pageFile === null) {
                    $this->components->warn("Skipping invalid file: {$file->getRelativePathname()}");

                    continue;
                }

                $parentId = $importer->resolveParent($pageFile->parentDirKey);

                if ($parentId === false) {
                    $this->components->twoColumnDetail(
                        "<fg=red>SKIP</>  {$pageFile->title}",
                        "{$pageFile->slug} — parent not found"
                    );
                    $this->skipped++;

                    continue;
                }

                [$result, $page] = $importer->import($pageFile, $parentId);

                $importedKeys->push($importer->buildUniqueKey($type, $locale, $parentId, $pageFile->slug));

                if (!$isDryRun && !empty($pageFile->blocks)) {
                    $importer->importMedia($page, $pageFile->blocks, $pageFile->sourceDir);
                }

                if (!empty($pageFile->seo)) {
                    $importer->updateSeo($page, $pageFile->seo);
                }

                $importer->cachePage($pageFile->dirKey, $page->id);

                $label = match ($result) {
                    ImportResult::Created => '<fg=green>CREATE</>',
                    ImportResult::Updated => '<fg=yellow>UPDATE</>',
                    ImportResult::Unchanged => '<fg=gray>SKIP</>',
                };
                $suffix = $result === ImportResult::Unchanged ? ' — unchanged' : '';

                $this->components->twoColumnDetail(
                    "{$label}  {$pageFile->title}",
                    $page->slug_path.$suffix
                );
            }

            if ($shouldPrune) {
                $this->pruneWithOutput($importer, $importedKeys, $type, $locale);
            }
        } finally {
            if ($isDryRun) {
                DB::rollBack();
            }
        }

        $this->newLine();
        $this->outputSummary($importer);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, SplFileInfo>
     */
    private function discoverFiles(string $basePath): Collection
    {
        return collect(File::allFiles($basePath))
            ->filter(fn (SplFileInfo $file): bool => in_array(strtolower($file->getExtension()), ['yaml', 'yml'], true))
            ->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                $depthA = $a->getRelativePath() === '' ? 0 : substr_count($a->getRelativePath(), DIRECTORY_SEPARATOR) + 1;
                $depthB = $b->getRelativePath() === '' ? 0 : substr_count($b->getRelativePath(), DIRECTORY_SEPARATOR) + 1;

                if ($depthA !== $depthB) {
                    return $depthA <=> $depthB;
                }

                $isIndexA = $a->getFilenameWithoutExtension() === '_index';
                $isIndexB = $b->getFilenameWithoutExtension() === '_index';

                if ($isIndexA !== $isIndexB) {
                    return $isIndexA ? -1 : 1;
                }

                return $a->getFilename() <=> $b->getFilename();
            })
            ->values();
    }

    /**
     * @param  Collection<int, string>  $importedKeys
     */
    private function pruneWithOutput(PageImporter $importer, Collection $importedKeys, string $type, ?string $locale): void
    {
        $pruned = $importer->pruneOrphans($importedKeys, $type, $locale);

        foreach ($pruned as $page) {
            $this->components->twoColumnDetail(
                "<fg=red>PRUNE</>  {$page->title}",
                $page->slug_path
            );
        }
    }

    private function outputSummary(PageImporter $importer): void
    {
        $parts = [];
        if ($importer->created > 0) {
            $parts[] = "{$importer->created} created";
        }
        if ($importer->updated > 0) {
            $parts[] = "{$importer->updated} updated";
        }
        if ($importer->unchanged > 0) {
            $parts[] = "{$importer->unchanged} unchanged";
        }
        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} skipped";
        }
        if ($importer->pruned > 0) {
            $parts[] = "{$importer->pruned} pruned";
        }

        $this->components->info('Summary: '.(empty($parts) ? 'nothing to do' : implode(', ', $parts)));
    }
}
