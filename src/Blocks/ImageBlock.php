<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Bambamboole\FilamentPages\Models\Page;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

#[IsBlock(type: 'image', label: 'Image')]
class ImageBlock extends AbstractBlock
{
    protected string $view = 'filament-pages::blocks.image';

    public function build(Block $block): Block
    {
        return $block
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
    public function render(array $data, Page $page): View
    {
        $media = null;

        if (!empty($data['image_collection_id'])) {
            $media = $page->getMedia($data['image_collection_id'])->first();
        }

        return view($this->view, [
            'image' => $media,
            'alt' => $data['alt'] ?? '',
        ]);
    }
}
