<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Layouts;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DefaultLayout implements PageLayout
{
    public static function name(): string
    {
        return 'default';
    }

    public static function label(): string
    {
        return 'Default';
    }

    public function render(Request $request, Page $page): View
    {
        return view('filament-pages::layouts.default', ['page' => $page]);
    }
}
