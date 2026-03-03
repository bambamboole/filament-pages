<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Filament\Forms\Components\Builder\Block;
use Illuminate\Database\Eloquent\Model;

abstract class PageBlock
{
    abstract public static function name(): string;

    abstract public static function make(): Block;

    /**
     * Transform block data before passing to the blade view.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateData(array $data, ?Model $record = null): array
    {
        return $data;
    }

    /**
     * Blade view name for frontend rendering.
     */
    public static function viewName(): string
    {
        return 'filament-pages::blocks.'.static::name();
    }
}
