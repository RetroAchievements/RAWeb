<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TrendingReason: string
{
    case NewSet = 'new-set';
    case RevisedSet = 'revised-set';
    case GainingTraction = 'gaining-traction';
    case RenewedInterest = 'renewed-interest';
    case ManyMorePlayers = 'many-more-players';
    case MorePlayers = 'more-players';
}
