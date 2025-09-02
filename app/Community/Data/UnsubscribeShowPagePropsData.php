<?php

declare(strict_types=1);

namespace App\Community\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UnsubscribeShowPageProps')]
class UnsubscribeShowPagePropsData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $error = null,
        public ?string $descriptionKey = null,
        #[LiteralTypeScriptType('Record<string, string> | null')]
        public ?array $descriptionParams = null,
        public ?string $undoToken = null,
    ) {
    }
}
