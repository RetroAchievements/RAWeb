<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\PlatformExecutionEnvironment;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Platform')]
class PlatformData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?PlatformExecutionEnvironment $executionEnvironment,
        public int $orderColumn,
    ) {
    }
}
