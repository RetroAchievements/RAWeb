<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ScreenshotUploadTypeStatus')]
class ScreenshotUploadTypeStatusData extends Data
{
    public function __construct(
        public int $count,
        public bool $hasResolutionIssues,
    ) {
    }
}
