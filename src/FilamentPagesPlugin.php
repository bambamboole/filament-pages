<?php

namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Filament\Pages\PageTreePage;
use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentPagesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-pages';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                PageResource::class,
            ])
            ->pages([
                PageTreePage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
