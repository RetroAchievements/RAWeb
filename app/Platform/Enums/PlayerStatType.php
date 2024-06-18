<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class PlayerStatType
{
    public const GamesBeatenHardcoreDemos = 'games_beaten_hardcore_demos';

    public const GamesBeatenHardcoreHacks = 'games_beaten_hardcore_hacks';

    public const GamesBeatenHardcoreHomebrew = 'games_beaten_hardcore_homebrew';

    public const GamesBeatenHardcorePrototypes = 'games_beaten_hardcore_prototypes';

    public const GamesBeatenHardcoreRetail = 'games_beaten_hardcore_retail';

    public const GamesBeatenHardcoreUnlicensed = 'games_beaten_hardcore_unlicensed';

    public const PointsHardcoreDay = 'points_hardcore_day';

    public const PointsHardcoreWeek = 'points_hardcore_week';

    public const PointsSoftcoreDay = 'points_softcore_day';

    public const PointsSoftcoreWeek = 'points_softcore_week';

    public const PointsWeightedDay = 'points_weighted_day';

    public const PointsWeightedWeek = 'points_weighted_week';

    public static function cases(): array
    {
        return [
            self::GamesBeatenHardcoreDemos,
            self::GamesBeatenHardcoreHacks,
            self::GamesBeatenHardcoreHomebrew,
            self::GamesBeatenHardcorePrototypes,
            self::GamesBeatenHardcoreRetail,
            self::GamesBeatenHardcoreUnlicensed,

            self::PointsHardcoreDay,
            self::PointsHardcoreWeek,
            self::PointsSoftcoreDay,
            self::PointsSoftcoreWeek,
            self::PointsWeightedDay,
            self::PointsWeightedWeek,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::cases());
    }
}
