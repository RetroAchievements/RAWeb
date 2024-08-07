<?php

namespace App\Data\TypeScript;

use App\Data\Transformers\SiteEnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;

class ConstantsGenerator
{
    public function generate(TypeScriptTransformer $transformer): void
    {
        // Run the main transformation process
        $transformer->transform();

        // After transformation, write the constants
        $constantsContent = "/* eslint-disable */\n/* generated with `composer types` */\n" . SiteEnumTransformer::getGeneratedConstants();
        file_put_contents(resource_path('js/common/utils/generatedAppConstants.ts'), $constantsContent);
    }
}
