<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController
{
    public function __invoke(Request $request): View
    {
        $model = config('filament-pages.model');
        $locale = $request->route('locale');
        $path = $request->route('path');

        $slugPath = (empty($path)) ? '/' : '/' . $path;

        $page = $model::query()
            ->where('locale', $locale)
            ->where('slug_path', $slugPath)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view(config('filament-pages.routing.layout'), ['page' => $page]);
    }
}
