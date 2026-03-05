<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Commands;

use Bambamboole\FilamentPages\Blocks\AbstractBlock;
use Bambamboole\FilamentPages\Blocks\IsBlock;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

use function Laravel\Prompts\text;

class MakeBlockCommand extends Command
{
    public $signature = 'filament-pages:make-block {name? : The name or FQCN of the block (e.g. Hero or App\\Custom\\HeroBlock)}';

    public $description = 'Create a new page block class and blade view';

    public function handle(): int
    {
        $name = $this->argument('name') ?? text(
            label: 'Block name or FQCN',
            placeholder: 'e.g. Hero or App\\Custom\\HeroBlock',
            required: true,
        );

        $resolved = $this->resolveClassDetails($name);

        $classPath = $this->generateClass($resolved['namespace'], $resolved['className'], $resolved['kebabName'], $resolved['filePath']);
        $viewPath = $this->generateView($resolved['kebabName']);

        $this->components->info('Block created successfully:');
        $this->components->bulletList([
            "Class: {$classPath}",
            "View: {$viewPath}",
        ]);

        $this->components->info('The block will be auto-discovered via the #[IsBlock] attribute.');

        return self::SUCCESS;
    }

    /**
     * @return array{namespace: string, className: string, kebabName: string, filePath: string}
     */
    private function resolveClassDetails(string $name): array
    {
        if (str_contains($name, '\\')) {
            $fqcn = $name;
            if (!str_ends_with($fqcn, 'Block')) {
                $fqcn .= 'Block';
            }

            $parts = explode('\\', $fqcn);
            $className = array_pop($parts);
            $namespace = implode('\\', $parts);
            $shortName = Str::beforeLast($className, 'Block');
            $kebabName = Str::kebab($shortName);

            $filePath = base_path($this->namespaceToPath($namespace).'/'.$className.'.php');

            return ['namespace' => $namespace, 'className' => $className, 'kebabName' => $kebabName, 'filePath' => $filePath];
        }

        $studlyName = Str::studly($name);
        $className = $studlyName.'Block';
        $namespace = 'App\\Blocks';
        $kebabName = Str::kebab($studlyName);
        $filePath = app_path("Blocks/{$className}.php");

        return ['namespace' => $namespace, 'className' => $className, 'kebabName' => $kebabName, 'filePath' => $filePath];
    }

    private function namespaceToPath(string $namespace): string
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $autoload = array_merge(
            $composerJson['autoload']['psr-4'] ?? [],
            $composerJson['autoload-dev']['psr-4'] ?? [],
        );

        foreach ($autoload as $prefix => $path) {
            $prefix = rtrim((string) $prefix, '\\');
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));

                return rtrim((string) $path, '/').$relative;
            }
        }

        return str_replace('\\', '/', $namespace);
    }

    private function generateClass(string $namespace, string $className, string $kebabName, string $filePath): string
    {
        $file = new PhpFile;
        $file->setStrictTypes();

        $ns = $file->addNamespace($namespace);
        $ns->addUse(AbstractBlock::class);
        $ns->addUse(IsBlock::class);
        $ns->addUse(Block::class);
        $ns->addUse(TextInput::class);

        $class = $ns->addClass($className);
        $class->setExtends(AbstractBlock::class);
        $class->addAttribute(IsBlock::class, ['type' => $kebabName, 'label' => Str::headline($kebabName)]);

        $class->addProperty('view')
            ->setType('string')
            ->setValue("blocks.{$kebabName}")
            ->setProtected();

        $build = $class->addMethod('build')
            ->setReturnType(Block::class);
        $build->addParameter('block')->setType(Block::class);
        $build->setBody(<<<'PHP'
return $block
    ->schema([
        // Add your form fields here
        TextInput::make('title')
            ->label('Title'),
    ]);
PHP);

        $printer = new PsrPrinter;
        $content = $printer->printFile($file);

        $this->ensureDirectoryExists(dirname($filePath));
        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateView(string $kebabName): string
    {
        $content = <<<'BLADE'
<div>
    {{-- Block content --}}
</div>
BLADE;

        $path = resource_path("views/blocks/{$kebabName}.blade.php");
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content."\n");

        return $path;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
