<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;
use RA\AwardType;

class PlayerBadge extends BaseModel
{
    // Note: will be renamed and split into Community/UserBadge and Platform/PlayerBadge
    protected $table = 'SiteAwards';

    private const DEVELOPER_COUNT_BOUNDARIES = [
        100,
        250,
        500,
        1000,
        2500,
        5000,
        10000,
        25000,
        50000,
        100000,
        250000,
        500000,
        1000000,
        2500000,
        5000000,
    ];

    private const DEVELOPER_POINT_BOUNDARIES = [
        1000,
        2500,
        5000,
        10000,
        25000,
        50000,
        100000,
        250000,
        500000,
        1000000,
        2500000,
        5000000,
        10000000,
        25000000,
        50000000,
    ];

    private static function getThresholds(int $awardType): ?array
    {
        switch ($awardType) {
            case AwardType::AchievementUnlocksYield:
                return self::DEVELOPER_COUNT_BOUNDARIES;

            case AwardType::AchievementPointsYield:
                return self::DEVELOPER_POINT_BOUNDARIES;

            default:
                return null;
        }
    }

    public static function getBadgeThreshold(int $awardType, int $tier): int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds === null)
            return 0;

        if ($tier < 0 || $tier >= count($thresholds))
            return 0;

        return $thresholds[$tier];
    }

    public static function getNewBadgeTier(int $awardType, int $oldValue, int $newValue): ?int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds !== null) {
            for ($i = count($thresholds) - 1; $i >= 0; $i--) {
                if ($newValue >= $thresholds[$i] && $oldValue < $thresholds[$i])
                    return $i;
            }
        }

        return null;
    }
}
