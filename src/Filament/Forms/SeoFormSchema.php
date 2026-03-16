<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Filament\Forms;

use Bambamboole\FilamentPages\Actions\GenerateOgImageAction;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Component;
use RalphJSmit\Filament\SEO\SEO;
use Spatie\Browsershot\Browsershot;

class SeoFormSchema
{
    /**
     * @return array<Component>
     */
    public static function make(): array
    {
        $plugin = FilamentPagesPlugin::get();

        $ogImageField = SpatieMediaLibraryFileUpload::make('og_image')
            ->label('OG Image')
            ->collection('og-image')
            ->disk('public')
            ->image()
            ->imageEditor()
            ->helperText('Recommended: 1200x630px. Used for social media sharing.');

        if (class_exists(Browsershot::class)) {
            $ogImageField = $ogImageField->hintAction(GenerateOgImageAction::make());
        }

        $components = [
            SEO::make(),
            $ogImageField,
        ];

        foreach ($plugin->getSeoFormCallbacks() as $callback) {
            $components = [...$components, ...$callback()];
        }

        return $components;
    }
}
