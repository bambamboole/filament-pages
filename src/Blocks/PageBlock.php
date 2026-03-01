<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Blocks;

use Filament\Forms\Components\Builder\Block;

interface PageBlock
{
    public static function make(): Block;
}
