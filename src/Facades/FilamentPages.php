<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Facades;

use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string model()
 * @method static array<class-string<\Bambamboole\FilamentPages\Blocks\PageBlock>> blockClasses()
 * @method static void setBlockClasses(?array $classes)
 * @method static void resetBlockCache()
 * @method static array<class-string<\Bambamboole\FilamentPages\Layouts\PageLayout>> layouts()
 * @method static void setLayoutClasses(?array $classes)
 * @method static array layoutOptions()
 * @method static array seoDefaults()
 * @method static array markdownConfig()
 * @method static void routes(string $prefix = '')
 * @method static array locales()
 * @method static string defaultLocale()
 * @method static bool hasLocales()
 */
class FilamentPages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FilamentPagesService::class;
    }
}
