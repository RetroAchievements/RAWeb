<?php

declare(strict_types=1);

namespace LegacyApp\Community\Enums;

abstract class RequestStatus
{
    public const Any = 0;
    public const Claimed = 1;
    public const Unclaimed = 2;

    public static function cases(): array
    {
        return [
            self::Any,
            self::Claimed,
            self::Unclaimed,
        ];
    }
}
