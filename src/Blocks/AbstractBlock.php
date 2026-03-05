<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Contracts\View\View;
use RalphJSmit\Laravel\SEO\SchemaCollection;

abstract class AbstractBlock implements PageBlock
{
    protected string $view = '';

    abstract public function build(Block $block): Block;

    /** {@inheritDoc} */
    public function registerSchema(SchemaCollection $schema, array $data, Page $page): SchemaCollection
    {
        return $schema;
    }

    /** {@inheritDoc} */
    public function render(array $data, Page $page): View
    {
        return view($this->view, $data);
    }
}
