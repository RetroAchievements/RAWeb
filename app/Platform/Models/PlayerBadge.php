<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Enums\AwardType;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerBadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerBadge extends BaseModel
{
    use HasFactory;

    // TODO Note: will be renamed and split into Community/UserBadge and Platform/PlayerBadge
    protected $table = 'SiteAwards';

    public const CREATED_AT = 'AwardDate';
    public const UPDATED_AT = null;

    protected $fillable = [
        'User',
        'AwardType',
        'AwardData',
        'AwardDataExtra',
        'AwardDate',
        'DisplayOrder',
    ];

    protected $casts = [
        'AwardType' => 'int',
        'AwardData' => 'int',
        'AwardDataExtra' => 'int',
        'DisplayOrder' => 'int',
    ];

    public const MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY = 6;

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

    protected static function newFactory(): PlayerBadgeFactory
    {
        return PlayerBadgeFactory::new();
    }

    public static function getBadgeThreshold(int $awardType, int $tier): int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds === null) {
            return 0;
        }

        if ($tier < 0 || $tier >= count($thresholds)) {
            return 0;
        }

        return $thresholds[$tier];
    }

    public static function getNewBadgeTier(int $awardType, int $oldValue, int $newValue): ?int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds !== null) {
            for ($i = count($thresholds) - 1; $i >= 0; $i--) {
                if ($newValue >= $thresholds[$i] && $oldValue < $thresholds[$i]) {
                    return $i;
                }
            }
        }

        return null;
    }

    public static function getNextDisplayOrder(User $user): int
    {
        return PlayerBadge::where('User', $user->User)->max('DisplayOrder') + 1;
    }

    /**
     * @return BelongsTo<User, PlayerBadge>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User', 'User');
    }
}
