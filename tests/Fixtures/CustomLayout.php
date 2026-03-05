<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\Layouts\AbstractLayout;
use Bambamboole\FilamentPages\Layouts\IsLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

#[IsLayout(key: 'custom', label: 'Custom')]
class CustomLayout extends AbstractLayout
{
    protected string $view = 'filament-pages::layouts.default';

    #[\Override]
    public function render(Request $request, Page $page): View
    {
        return view($this->view, ['page' => $page]);
    }
}
