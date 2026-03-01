<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class ImageBlock implements PageBlock
{
    public static function make(): Block
    {
        return Block::make('image')
            ->label('Image')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->collection(fn (SpatieMediaLibraryFileUpload $component, Get $get) => $get('image_collection_id') ?? $component->getContainer()->getStatePath())
                    ->afterStateHydrated(null)
                    ->mutateDehydratedStateUsing(null)
                    ->image()
                    ->imageEditor()
                    ->responsiveImages()
                    ->afterStateUpdated(fn (SpatieMediaLibraryFileUpload $component, Set $set) => $set('image_collection_id', $component->getContainer()->getStatePath()))
                    ->live()
                    ->required(),
                Hidden::make('image_collection_id'),
                TextInput::make('alt')
                    ->label('Alt Text'),
            ]);
    }
}
