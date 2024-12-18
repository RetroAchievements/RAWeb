<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameSetType: string
{
    case Hub = "hub";
    case SimilarGames = "similar-games";
}
