<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Layouts;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class AbstractLayout implements PageLayout
{
    protected string $view = '';

    public function render(Request $request, Page $page): View
    {
        return view($this->view, ['page' => $page]);
    }
}
