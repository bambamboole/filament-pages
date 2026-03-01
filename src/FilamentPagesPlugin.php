<?php

namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Blocks\ImageBlock;
use Bambamboole\FilamentPages\Blocks\MarkdownBlock;
use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Filament\Pages\PageTreePage;
use Bambamboole\FilamentPages\Filament\Resources\PageResource;
use Bambamboole\FilamentPages\Layouts\PageLayout;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Builder\Block;
use Filament\Panel;
use Filament\Resources\Resource;

class FilamentPagesPlugin implements Plugin
{
    /** @var class-string<resource>|null */
    protected ?string $resource = null;

    /** @var array<string, string> */
    protected array $locales = [];

    /** @var array<class-string<PageBlock>> */
    protected array $blocks = [];

    /** @var array<class-string<PageLayout>> */
    protected array $layouts = [];

    /** @var array<Closure> */
    protected array $treeItemActionCallbacks = [];

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
            : config('filament-pages.blocks', [MarkdownBlock::class, ImageBlock::class]);

        return array_map(
            fn (string $blockClass): Block => $blockClass::make(),
            $blockClasses,
        );
    }

    /**
     * @param  array<class-string<PageLayout>>  $layouts
     */
    public function layouts(array $layouts): static
    {
        $this->layouts = $layouts;

        return $this;
    }

    /**
     * @return array<class-string<PageLayout>>
     */
    public function getLayouts(): array
    {
        return $this->layouts !== []
            ? $this->layouts
            : config('filament-pages.layouts', []);
    }

    /**
     * @return array<string, string>
     */
    public function getLayoutOptions(): array
    {
        return collect($this->getLayouts())
            ->mapWithKeys(fn (string $class): array => [$class::name() => $class::label()])
            ->toArray();
    }

    /**
     * @param  class-string<resource>  $resource
     */
    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string<resource>
     */
    public function getResource(): string
    {
        return $this->resource ?? config('filament-pages.resource', PageResource::class);
    }

    public function treeItemActions(Closure $callback): static
    {
        $this->treeItemActionCallbacks[] = $callback;

        return $this;
    }

    /**
     * @return array<Closure>
     */
    public function getTreeItemActionCallbacks(): array
    {
        return $this->treeItemActionCallbacks;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                $this->getResource(),
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
