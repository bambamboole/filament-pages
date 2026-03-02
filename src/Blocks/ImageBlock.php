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
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class ImageBlock extends PageBlock
{
    public static function name(): string
    {
        return 'image';
    }

    public static function make(): Block
    {
        return Block::make(static::name())
            ->label('Image')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->collection(fn (SpatieMediaLibraryFileUpload $component, Get $get): mixed => $get('image_collection_id') ?? $component->getContainer()->getStatePath())
                    ->afterStateHydrated(null)
                    ->mutateDehydratedStateUsing(null)
                    ->image()
                    ->imageEditor()
                    ->responsiveImages()
                    ->afterStateUpdated(fn (SpatieMediaLibraryFileUpload $component, Set $set): mixed => $set('image_collection_id', $component->getContainer()->getStatePath()))
                    ->live()
                    ->required(),
                Hidden::make('image_collection_id'),
                TextInput::make('alt')
                    ->label('Alt Text'),
            ]);
    }

    /** {@inheritDoc} */
    #[\Override]
    public static function mutateData(array $data, ?Model $record = null): array
    {
        $media = null;

        if ($record instanceof HasMedia && ! empty($data['image_collection_id'])) {
            $media = $record->getMedia($data['image_collection_id'])->first();
        }

        return [
            'image' => $media,
            'alt' => $data['alt'] ?? '',
        ];
    }
}
