<?php

namespace Bambamboole\FilamentPages\Filament\Resources\PageResource\Pages;

use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('visitPage')
                ->label('Visit Page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): ?string => $this->record->frontendUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => $this->record->isPublished()),
            Actions\DeleteAction::make(),
        ];
    }
}
