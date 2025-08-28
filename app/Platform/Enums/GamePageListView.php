<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GamePageListView: string
{
    case Achievements = 'achievements';
    case Leaderboards = 'leaderboards';
}
