<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserGameListType: string
{
    case AchievementSetRequest = 'achievement_set_request';

    case Play = 'play';

    case Develop = 'develop';
}
