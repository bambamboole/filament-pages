<?php
declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Contracts\View\View;
use RalphJSmit\Laravel\SEO\SchemaCollection;

interface PageBlock
{
    public function build(Block $block): Block;

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerSchema(SchemaCollection $schema, array $data, Page $page): SchemaCollection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(array $data, Page $page): View;

    /** @return array{css: list<BlockAsset>, js: list<BlockAsset>} */
    public function assets(): array;
}
