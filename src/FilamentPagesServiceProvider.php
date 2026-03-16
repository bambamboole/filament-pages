<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages;

use Bambamboole\FilamentPages\Blocks\BlockAssetBag;
use Bambamboole\FilamentPages\Commands\ExportPagesCommand;
use Bambamboole\FilamentPages\Commands\ImportPagesCommand;
use Bambamboole\FilamentPages\Commands\MakeBlockCommand;
use Bambamboole\FilamentPages\Commands\MakeLayoutCommand;
use Bambamboole\FilamentPages\Services\FilamentPagesService;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPagesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-pages';

    public static string $viewNamespace = 'filament-pages';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('bambamboole/filament-pages');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(FilamentPagesService::class, fn (): FilamentPagesService => new FilamentPagesService);
        $this->app->scoped(BlockAssetBag::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            [
                Css::make('filament-pages', __DIR__.'/../resources/dist/css/filament-pages.css'),
                Js::make('filament-pages', __DIR__.'/../resources/dist/js/filament-pages.js'),
            ],
            'bambamboole/filament-pages'
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__.'/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-pages/{$file->getFilename()}"),
                ], 'filament-pages-stubs');
            }
        }

        // Blade directive for frontend styles
        Blade::directive('filamentPagesStyles', function (): string {
            $path = __DIR__.'/../resources/css/frontend.css';

            return "<?php echo '<style' . ((\Illuminate\Support\Facades\Vite::cspNonce() ? ' nonce=\"' . \Illuminate\Support\Facades\Vite::cspNonce() . '\"' : '')) . '>' . file_get_contents('{$path}') . '</style>'; ?>";
        });

        Blade::directive('filamentPagesBlockStyles', fn (): string => "<?php echo app(\Bambamboole\FilamentPages\Blocks\BlockAssetBag::class)->renderStyles(); ?>");

        Blade::directive('filamentPagesBlockScripts', fn (): string => "<?php echo app(\Bambamboole\FilamentPages\Blocks\BlockAssetBag::class)->renderScripts(); ?>");

    }

    protected function getAssetPackageName(): ?string
    {
        return 'bambamboole/filament-pages';
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            ExportPagesCommand::class,
            ImportPagesCommand::class,
            MakeBlockCommand::class,
            MakeLayoutCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_pages_table',
            'create_seo_table',
        ];
    }
}
