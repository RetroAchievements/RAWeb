<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class PlayerBadge extends BaseModel
{
    // Note: will be renamed and split into Community/UserBadge and Platform/PlayerBadge
    protected $table = 'SiteAwards';

    const CREATED_AT = 'AwardDate';
    const UPDATED_AT = null;

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
        1_000_000,
        2_500_000,
        5_000_000,
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
        1_000_000,
        2_500_000,
        5_000_000,
        10_000_000,
        25_000_000,
        50_000_000,
    ];

    private static function getThresholds(int $awardType): ?array
    {
        return match ($awardType) {
            AwardType::AchievementUnlocksYield => self::DEVELOPER_COUNT_BOUNDARIES,
            AwardType::AchievementPointsYield => self::DEVELOPER_POINT_BOUNDARIES,
            default => null,
        };
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
