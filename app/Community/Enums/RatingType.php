<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class RatingType
{
    public const Game = 1;

    public const User = 2;

    public const Achievement = 3;

    public static function cases(): array
    {
        return [
            self::Game,
            self::User,
            self::Achievement,
        ];
    }
}
