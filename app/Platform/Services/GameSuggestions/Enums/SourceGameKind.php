<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum SourceGameKind: string
{
    case Beaten = 'beaten';
    case Mastered = 'mastered';
    case WantToPlay = 'want-to-play';
}
