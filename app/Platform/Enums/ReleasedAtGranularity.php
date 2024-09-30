<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ReleasedAtGranularity: string
{
    case Day = "day";
    case Month = "month";
    case Year = "year";
}
