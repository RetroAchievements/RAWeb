<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameBannerPreference: string
{
    case Compact = 'compact';
    case Normal = 'normal';
    case Expanded = 'expanded';
}
