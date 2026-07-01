<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum PlayerPreferredMode: string
{
    case Casual = 'casual';
    case Hardcore = 'hardcore';
    case Mixed = 'mixed';
}
