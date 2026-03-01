<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Commands;

use Bambamboole\FilamentPages\Layouts\PageLayout;
use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

use function Laravel\Prompts\text;

class MakeLayoutCommand extends Command
{
    public $signature = 'filament-pages:make-layout {name? : The name or FQCN of the layout (e.g. Blog or App\\Layouts\\BlogLayout)}';

    public $description = 'Create a new page layout class and blade view';

    public function handle(): int
    {
        $name = $this->argument('name') ?? text(
            label: 'Layout name or FQCN',
            placeholder: 'e.g. Blog or App\\Layouts\\BlogLayout',
            required: true,
        );

        $resolved = $this->resolveClassDetails($name);

        $classPath = $this->generateClass($resolved['namespace'], $resolved['className'], $resolved['kebabName'], $resolved['filePath']);
        $viewPath = $this->generateView($resolved['kebabName']);

        $this->components->info('Layout created successfully:');
        $this->components->bulletList([
            "Class: {$classPath}",
            "View: {$viewPath}",
        ]);

        $this->components->warn("Don't forget to register the layout in your config/filament-pages.php layouts array.");

        return self::SUCCESS;
    }

    /**
     * @return array{namespace: string, className: string, kebabName: string, filePath: string}
     */
    private function resolveClassDetails(string $name): array
    {
        if (str_contains($name, '\\')) {
            $fqcn = $name;
            if (! str_ends_with($fqcn, 'Layout')) {
                $fqcn .= 'Layout';
            }

            $parts = explode('\\', $fqcn);
            $className = array_pop($parts);
            $namespace = implode('\\', $parts);
            $shortName = Str::beforeLast($className, 'Layout');
            $kebabName = Str::kebab($shortName);

            $filePath = base_path($this->namespaceToPath($namespace) . '/' . $className . '.php');

            return compact('namespace', 'className', 'kebabName', 'filePath');
        }

        $studlyName = Str::studly($name);
        $className = $studlyName . 'Layout';
        $namespace = 'App\\Layouts';
        $kebabName = Str::kebab($studlyName);
        $filePath = app_path("Layouts/{$className}.php");

        return compact('namespace', 'className', 'kebabName', 'filePath');
    }

    private function namespaceToPath(string $namespace): string
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $autoload = array_merge(
            $composerJson['autoload']['psr-4'] ?? [],
            $composerJson['autoload-dev']['psr-4'] ?? [],
        );

        foreach ($autoload as $prefix => $path) {
            $prefix = rtrim($prefix, '\\');
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));

                return rtrim($path, '/') . $relative;
            }
        }

        return str_replace('\\', '/', $namespace);
    }

    private function generateClass(string $namespace, string $className, string $kebabName, string $filePath): string
    {
        $file = new PhpFile;
        $file->setStrictTypes();

        $ns = $file->addNamespace($namespace);
        $ns->addUse(PageLayout::class);
        $ns->addUse(Page::class);
        $ns->addUse(Request::class);
        $ns->addUse(View::class);

        $class = $ns->addClass($className);
        $class->addImplement(PageLayout::class);

        $class->addMethod('name')
            ->setStatic()
            ->setReturnType('string')
            ->setBody('return ?;', [$kebabName]);

        $shortName = Str::beforeLast($className, 'Layout');

        $class->addMethod('label')
            ->setStatic()
            ->setReturnType('string')
            ->setBody('return ?;', [Str::title(Str::snake($shortName, ' '))]);

        $render = $class->addMethod('render')
            ->setReturnType(View::class);
        $render->addParameter('request')->setType(Request::class);
        $render->addParameter('page')->setType(Page::class);
        $render->setBody(<<<PHP
// TODO: Update the view name to match your application's convention
return view('layouts.{$kebabName}', ['page' => \$page]);
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
<!DOCTYPE html>
<html lang="{{ $page->locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
</head>
<body>
    <main>
        <h1>{{ $page->title }}</h1>
        {!! $page->renderBlocks() !!}
    </main>
</body>
</html>
BLADE;

        $path = resource_path("views/layouts/{$kebabName}.blade.php");
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content . "\n");

        return $path;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
