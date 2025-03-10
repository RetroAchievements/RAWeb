<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameListSetTypeFilterValue: string
{
    case Both = 'both';
    case OnlyGames = 'only-games';
    case OnlySubsets = 'only-subsets';
}
