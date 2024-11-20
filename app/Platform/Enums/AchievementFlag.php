<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AchievementFlag: int
{
    case OfficialCore = 3;

    case Unofficial = 5;

    public function label(): string
    {
        return match ($this) {
            self::OfficialCore => __('Published'),
            self::Unofficial => __('Unpublished'),
        };
    }
}
