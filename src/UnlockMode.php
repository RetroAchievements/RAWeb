<?php

namespace RA;

abstract class UnlockMode
{
    public const Softcore = 0;

    public const Hardcore = 1;

    private const VALID = [
        self::Softcore,
        self::Hardcore,
    ];

    public static function isValid(int $type): bool
    {
        return in_array($type, self::VALID);
    }
}
