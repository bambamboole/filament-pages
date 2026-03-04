<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Observers;

use Bambamboole\FilamentPages\Models\Page;
use Spatie\ResponseCache\Facades\ResponseCache;

class PageObserver
{
    public function saved(Page $page): void
    {
        $this->forgetPageCache($page);
    }

    public function deleted(Page $page): void
    {
        $this->forgetPageCache($page);
    }

    private function forgetPageCache(Page $page): void
    {
        if (config('filament-pages.cache.enabled')) {
            $prefix = config('filament-pages.routing.prefix', '');
            $path = rtrim((string) $prefix, '/').$page->localePrefixedPath();

            ResponseCache::forget($path);
        }
    }
}
