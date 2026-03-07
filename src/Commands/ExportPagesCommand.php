<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Commands;

use Bambamboole\FilamentPages\Exports\PageExporter;
use Bambamboole\FilamentPages\Imports\PageFile;
use Illuminate\Console\Command;

class ExportPagesCommand extends Command
{
    public $signature = 'filament-pages:export
        {--path= : Destination directory (default: resources/pages)}
        {--locale= : Filter by locale}
        {--type=page : Page type to export}';

    public $description = 'Export pages from the database to YAML files';

    public function handle(PageExporter $exporter): int
    {
        $basePath = $this->option('path') ?? resource_path('pages');
        $locale = $this->option('locale');
        $type = $this->option('type');

        $this->components->info("Exporting pages to: {$basePath}");
        $this->components->twoColumnDetail('Locale', $locale ?: '(none)');
        $this->components->twoColumnDetail('Type', $type);
        $this->newLine();

        $pages = $exporter->queryPages($type, $locale);

        if ($pages->isEmpty()) {
            $this->components->warn('No pages found to export.');

            return self::SUCCESS;
        }

        $entries = $exporter->buildExportOrder($pages);

        foreach ($entries as $entry) {
            $page = $entry['page'];
            $hasChildren = $entry['hasChildren'];

            $relativePath = $exporter->resolveFilePath($page, $hasChildren);
            $fullPath = $basePath.'/'.$relativePath;

            $blocks = $page->blocks ?? [];
            $exporter->exportMedia($page, $blocks, dirname($fullPath));

            $page->blocks = $blocks;

            $pageFile = PageFile::fromPage($page, dirname($fullPath));
            $data = $pageFile->toYamlArray();

            $exporter->writeYaml($fullPath, $data);

            $this->components->twoColumnDetail(
                "<fg=green>EXPORT</>  {$page->title}",
                $relativePath
            );
        }

        $this->newLine();

        $parts = ["{$exporter->exported} exported"];
        if ($exporter->mediaExported > 0) {
            $parts[] = "{$exporter->mediaExported} media files";
        }

        $this->components->info('Summary: '.implode(', ', $parts));

        return self::SUCCESS;
    }
}
