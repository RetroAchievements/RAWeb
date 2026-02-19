<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AchievementPageTab: string
{
    case Changelog = 'changelog';
    case Comments = 'comments';
    case Tips = 'tips';
    case Unlocks = 'unlocks';
}
