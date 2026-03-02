<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Http\Controllers;

use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Bambamboole\FilamentPages\Layouts\DefaultLayout;
use Bambamboole\FilamentPages\Layouts\PageLayout;
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

        $layout = $this->resolveLayout($page->layout);

        return $layout->render($request, $page);
    }

    private function resolveLayout(?string $layoutKey): PageLayout
    {
        /** @var array<class-string<PageLayout>> $layoutClasses */
        $layoutClasses = $this->getLayoutClasses();

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

    /**
     * Resolve layout classes from the plugin instance first, then fall back to config.
     *
     * @return array<class-string<PageLayout>>
     */
    private function getLayoutClasses(): array
    {
        try {
            return FilamentPagesPlugin::get()->getLayouts();
        } catch (\Throwable) {
            // Plugin may not be booted yet in non-panel contexts (e.g. API routes)
            return config('filament-pages.layouts', []);
        }
    }
}
