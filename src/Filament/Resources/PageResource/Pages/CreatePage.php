<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages;

use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Filament\Resources\Pages\CreateRecord;
use Pboivin\FilamentPeek\Pages\Actions\PreviewAction;
use Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal;

class CreatePage extends CreateRecord
{
    use HasPreviewModal;

    protected static string $resource = PageResource::class;

    protected function getPreviewModalView(): ?string
    {
        return FilamentPagesPlugin::get()->getPreviewView();
    }

    protected function getPreviewModalDataRecordKey(): string
    {
        return 'page';
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return array_filter([
            FilamentPagesPlugin::get()->isPreviewEnabled()
                ? PreviewAction::make()
                : null,
        ]);
    }
}
