<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Blocks\PageBlock;
use Bambamboole\FilamentPages\Filament\Pages\ManagePages;
use Bambamboole\FilamentPages\Layouts\PageLayout;
use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Builder\Block;
use Filament\Panel;
use Pboivin\FilamentPeek\FilamentPeekPlugin;

class FilamentPagesPlugin implements Plugin
{
    /** @var array<Closure> */
    protected array $treeItemActionCallbacks = [];

    /** @var array<Closure> */
    protected array $seoFormCallbacks = [];

    public function getId(): string
    {
        return 'filament-pages';
    }

    /**
     * @return array<class-string<PageBlock>>
     */
    public function getBlockClasses(): array
    {
        return app(FilamentPagesService::class)->blockClasses();
    }

    /**
     * @return array<Block>
     */
    public function getBuilderBlocks(): array
    {
        return array_map(
            fn (string $blockClass): Block => $blockClass::make(),
            $this->getBlockClasses(),
        );
    }

    /**
     * @return array<class-string<PageLayout>>
     */
    public function getLayouts(): array
    {
        return app(FilamentPagesService::class)->layouts();
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

    public function seoForm(Closure $callback): static
    {
        $this->seoFormCallbacks[] = $callback;

        return $this;
    }

    /**
     * @return array<Closure>
     */
    public function getSeoFormCallbacks(): array
    {
        return $this->seoFormCallbacks;
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
        $panel->pages([
            ManagePages::class,
        ]);

        $hasFilamentPeek = collect($panel->getPlugins())
            ->contains(fn (Plugin $plugin): bool => $plugin instanceof FilamentPeekPlugin);

        if (!$hasFilamentPeek) {
            $panel->plugin(FilamentPeekPlugin::make());
        }
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
