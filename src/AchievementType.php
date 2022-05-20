<?php

namespace RA;

abstract class AchievementType
{
    public const OFFICIAL_CORE = 3;
    public const UNOFFICIAL = 5;

    public const FLAGS = [
        self::OFFICIAL_CORE,
        self::UNOFFICIAL,
    ];
}
