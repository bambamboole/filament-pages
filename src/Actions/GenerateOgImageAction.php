<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Actions;

use Bambamboole\FilamentPages\Models\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class GenerateOgImageAction
{
    public static function make(): Action
    {
        return Action::make('generateOgImage')
            ->label('Generate')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->cancelParentActions(false)
            ->visible(fn (): bool => class_exists(Browsershot::class))
            ->action(function (Page $record, Set $set): void {
                if (!$record->exists) {
                    Notification::make()
                        ->title('Please save the page first')
                        ->warning()
                        ->send();

                    return;
                }

                $title = $record->seo?->title
                    ?? $record->title
                    ?? config('filament-pages.seo.defaults.og_title', '');

                $description = $record->seo?->description
                    ?? self::extractDescription($record)
                    ?? config('filament-pages.seo.defaults.og_description', '');

                $url = $record->frontendUrl() ?? url('/');

                $html = view('filament-pages::og-image', ['title' => $title, 'description' => $description, 'url' => $url])->render();

                $tempPath = storage_path('app/og-image-'.$record->id.'.png');

                Browsershot::html($html)
                    ->windowSize(1200, 630)
                    ->save($tempPath);

                $record->clearMediaCollection('og-image');
                $media = $record->addMedia($tempPath)->toMediaCollection('og-image', 'public');

                $set('og_image', [$media->uuid]);

                Notification::make()
                    ->title('OG image generated')
                    ->success()
                    ->send();
            });
    }

    private static function extractDescription(Page $record): ?string
    {
        $blocks = $record->blocks ?? [];

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'markdown' && !empty($block['data']['content'])) {
                return Str::limit(strip_tags((string) $block['data']['content']), 160);
            }
        }

        return null;
    }
}
