<?php

namespace RA;

abstract class ClaimSpecial
{
    public const None = 0;
    public const OwnRevision = 1;
    public const FreeRollout = 2;
    public const ScheduledRelease = 3;

    public static function toString(int $type): string
    {
        return match ($type) {
            self::None => "None",
            self::OwnRevision => "Own Revision",
            self::FreeRollout => "Free Rollout",
            self::ScheduledRelease => "Release Scheduled",
            default => "Invalid special",
        };
    }
}
