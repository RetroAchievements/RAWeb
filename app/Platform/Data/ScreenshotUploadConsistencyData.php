<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ScreenshotUploadConsistency')]
class ScreenshotUploadConsistencyData extends Data
{
    /**
     * @param array<int, array{width: int, height: int}> $existingResolutions
     */
    public function __construct(
        #[LiteralTypeScriptType('Array<{ width: number; height: number }>')]
        public array $existingResolutions,
        public string|Optional $canonicalResolution = new Optional(),
    ) {
    }
}
