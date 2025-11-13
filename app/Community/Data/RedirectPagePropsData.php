<?php

namespace App\Community\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RedirectPagePropsData')]
class RedirectPagePropsData extends Data
{
    public function __construct(
        public string $url,
    ) {
    }
}
