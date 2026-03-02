<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Forms;

use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use RalphJSmit\Filament\SEO\SEO;

class SeoFormSchema
{
    /**
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function make(): array
    {
        $plugin = FilamentPagesPlugin::get();

        $components = [
            SEO::make(),

            SpatieMediaLibraryFileUpload::make('og_image')
                ->label('OG Image')
                ->collection('og-image')
                ->disk('public')
                ->image()
                ->imageEditor()
                ->helperText('Recommended: 1200x630px. Used for social media sharing.'),
        ];

        foreach ($plugin->getSeoFormCallbacks() as $callback) {
            $components = [...$components, ...$callback()];
        }

        return $components;
    }
}
