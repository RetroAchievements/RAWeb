<?php

namespace App\Console\Commands;

use App\Data\TypeScript\ConstantsGenerator;
use Illuminate\Console\Command;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class GenerateTypeScript extends Command
{
    protected $signature = 'typescript:generate';
    protected $description = 'Generate TypeScript definitions and constants';

    public function handle(): void
    {
        $configArray = config('typescript-transformer');

        $config = TypeScriptTransformerConfig::create();

        foreach ($configArray['auto_discover_types'] as $path) {
            $config->autoDiscoverTypes($path);
        }

        $config
            ->collectors($configArray['collectors'])
            ->transformers($configArray['transformers'])
            ->defaultTypeReplacements($configArray['default_type_replacements'])
            ->outputFile($configArray['output_file'])
            ->transformToNativeEnums($configArray['transform_to_native_enums']);

        if (isset($configArray['writer'])) {
            $config->writer($configArray['writer']);
        }

        if (isset($configArray['formatter'])) {
            $config->formatter($configArray['formatter']);
        }

        $transformer = new TypeScriptTransformer($config);

        $generator = new ConstantsGenerator();
        $generator->generate($transformer);

        $this->info('TypeScript definitions and constants generated successfully.');
    }
}
