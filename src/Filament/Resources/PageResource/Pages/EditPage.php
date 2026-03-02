<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages;

use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Bambamboole\FilamentPages\FilamentPagesPlugin;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Pboivin\FilamentPeek\Pages\Actions\PreviewAction;
use Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal;

class EditPage extends EditRecord
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
            Actions\Action::make('visitPage')
                ->label('Visit Page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): ?string => $this->record->frontendUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => $this->record->isPublished()),
            Actions\DeleteAction::make(),
        ]);
    }
}
