<?php

namespace Bambamboole\FilamentPages\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bambamboole\FilamentPages\FilamentPages
 */
class FilamentPages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bambamboole\FilamentPages\FilamentPages::class;
    }
}
