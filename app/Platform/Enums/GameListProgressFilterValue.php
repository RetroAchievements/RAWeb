<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameListProgressFilterValue: string
{
    case Unstarted = 'unstarted';
    case Unfinished = 'unfinished';
    case GteBeatenSoftcore = 'gte_beaten_softcore';
    case GteBeatenHardcore = 'gte_beaten_hardcore';
    case EqBeatenSoftcore = 'eq_beaten_softcore';
    case EqBeatenHardcore = 'eq_beaten_hardcore';
    case GteCompleted = 'gte_completed';
    case EqCompleted = 'eq_completed';
    case EqMastered = 'eq_mastered';
    case Revised = 'revised';
    case NeqMastered = 'neq_mastered';
}
