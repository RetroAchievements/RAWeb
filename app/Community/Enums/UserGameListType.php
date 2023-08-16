<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class UserGameListType
{
    public const AchievementSetRequest = 'achievement_set_request';

    public const Play = 'play';

    public const Develop = 'develop';

    public static function cases(): array
    {
        return [
            self::AchievementSetRequest,
            self::Play,
            self::Develop,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::cases());
    }
}
