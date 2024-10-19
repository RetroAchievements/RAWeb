<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeJsComponent extends Command
{
    protected $signature = 'make:js-component {name} {feature?}';
    protected $description = 'Generate a new React component with a test and barrel file';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $feature = $this->argument('feature') ?? 'common';
        $componentDir =
            $feature === "common"
                ? "js/{$feature}/components/{$name}"
                : "js/features/{$feature}/components/{$name}";

        $componentResourcePath = resource_path($componentDir);

        if ($this->files->isDirectory($componentResourcePath)) {
            $this->error("Component {$name} already exists in {$feature}.");

            return;
        }

        $this->files->makeDirectory($componentResourcePath, recursive: true);

        $this->generateFile($componentResourcePath, "{$name}.tsx", 'component.stub', $name);
        $this->generateFile($componentResourcePath, "{$name}.test.tsx", 'component-test.stub', $name);
        $this->generateFile($componentResourcePath, "index.ts", 'component-barrel.stub', $name);

        $io = new SymfonyStyle($this->input, $this->output);
        $io->text("<info>CREATE</info> [{$componentDir}/{$name}.tsx]");
        $io->text("<info>CREATE</info> [{$componentDir}/{$name}.test.tsx]");
        $io->text("<info>CREATE</info> [{$componentDir}/{$name}/index.ts]");
    }

    private function generateFile(string $directory, string $fileName, string $stubName, string $componentName): void
    {
        $path = "{$directory}/{$fileName}";
        $stub = $this->getStub($stubName);
        $content = str_replace('{{componentName}}', $componentName, $stub);
        $this->files->put($path, $content);
    }

    private function getStub(string $stubName): string
    {
        return $this->files->get("stubs/js/{$stubName}");
    }
}
