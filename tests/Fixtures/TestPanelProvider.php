<?php

namespace Bambamboole\FilamentPages\Tests\Fixtures;

use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('test')
            ->path('test')
            ->login()
            ->plugins([
                FilamentPagesPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
