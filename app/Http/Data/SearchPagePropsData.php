<?php

declare(strict_types=1);

namespace App\Http\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('SearchPageProps')]
class SearchPagePropsData extends Data
{
    public function __construct(
        public string $initialQuery,
        public string $initialScope,
        public int $initialPage,
    ) {
    }
}
