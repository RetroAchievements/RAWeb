<?php

declare(strict_types=1);

namespace LegacyApp\Community\Enums;

abstract class UserGameListType
{
    public const SetRequest = 1;

    public const WantToPlay = 2;

    public const WantToDev = 3;

    public static function cases(): array
    {
        return [
            self::SetRequest,
            self::WantToPlay,
            self::WantToDev,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::cases());
    }
}
