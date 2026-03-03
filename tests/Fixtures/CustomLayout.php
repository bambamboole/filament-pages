<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\Layouts\PageLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomLayout implements PageLayout
{
    public static function name(): string
    {
        return 'custom';
    }

    public static function label(): string
    {
        return 'Custom';
    }

    public function render(Request $request, Page $page): View
    {
        return view('filament-pages::layouts.default', ['page' => $page]);
    }
}
