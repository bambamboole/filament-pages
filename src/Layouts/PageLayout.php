<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Layouts;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

interface PageLayout
{
    public function render(Request $request, Page $page): View;
}
