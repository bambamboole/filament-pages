<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Http\Controllers;

use Bambamboole\FilamentPages\Facades\FilamentPages;
use Bambamboole\FilamentPages\Layouts\DefaultLayout;
use Bambamboole\FilamentPages\Layouts\PageLayout;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController
{
    public function __invoke(Request $request): View
    {
        $locale = $request->route('locale');
        $path = $request->route('path');
        $slugPath = empty($path) ? '/' : '/'.$path;
        $model = FilamentPages::model();

        $page = $model::query()
            ->where('locale', $locale)
            ->where('slug_path', $slugPath)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return $this->resolveLayout($page->layout)->render($request, $page);
    }

    private function resolveLayout(?string $layoutKey): PageLayout
    {
        /** @var array<class-string<PageLayout>> $layoutClasses */
        $layoutClasses = FilamentPages::layouts();

        if ($layoutClasses === []) {
            return new DefaultLayout;
        }

        $map = [];
        foreach ($layoutClasses as $class) {
            $map[$class::name()] = $class;
        }

        $layoutClass = $map[$layoutKey] ?? reset($layoutClasses);

        return new $layoutClass;
    }
}
