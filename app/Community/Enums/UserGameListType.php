<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class UserGameListType
{
    public const SetRequest = 'set-request';

    public const WantToPlay = 'want-to-play';

    public const WantToDev = 'want-to-dev';

    public static function cases(): array
    {
        return [
            self::SetRequest,
            self::WantToPlay,
            self::WantToDev,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::cases());
    }
}
