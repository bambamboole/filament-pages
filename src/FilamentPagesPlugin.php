<?php

namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Blocks\ImageBlock;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Filament\Pages\PageTreePage;
use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Builder\Block;
use Filament\Panel;

class FilamentPagesPlugin implements Plugin
{
    /** @var array<string, string> */
    protected array $locales = [];

    /** @var array<class-string<PageBlock>> */
    protected array $blocks = [];

    public function getId(): string
    {
        return 'filament-pages';
    }

    /**
     * @param  array<string, string>  $locales  e.g. ['en' => 'English', 'de' => 'Deutsch']
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    public function hasLocales(): bool
    {
        return $this->locales !== [];
    }

    /**
     * @param  array<class-string<PageBlock>>  $blocks
     */
    public function blocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @return array<Block>
     */
    public function getBuilderBlocks(): array
    {
        $blockClasses = $this->blocks !== []
            ? $this->blocks
            : [MarkdownBlock::class, ImageBlock::class];

        return array_map(
            fn (string $blockClass): Block => $blockClass::make(),
            $blockClasses,
        );
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
