<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerBadgeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerBadge extends BaseModel
{
    /** @use HasFactory<PlayerBadgeFactory> */
    use HasFactory;

    // TODO rename PlayerBadge to UserAward
    protected $table = 'user_awards';

    public const CREATED_AT = 'awarded_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'award_type',
        'award_data',
        'award_data_extra',
        'awarded_at',
        'order_column',
    ];

    protected $casts = [
        'award_type' => AwardType::class,
        'award_data' => 'int',
        'award_data_extra' => 'int',
        'order_column' => 'int',
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

    private static function getThresholds(AwardType $awardType): ?array
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

    public static function getBadgeThreshold(AwardType $awardType, int $tier): int
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

    public static function getNewBadgeTier(AwardType $awardType, int $oldValue, int $newValue): ?int
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
        return PlayerBadge::where('user_id', $user->id)->max('order_column') + 1;
    }

    public static function getHighestUserAwardForGameId(User $user, int $gameId): ?array
    {
        $userAwards = $user->playerBadges()->forGameId($gameId)->get();

        $prestigeOrder = [
            ['type' => AwardType::Mastery, 'isHardcore' => 1, 'label' => 'mastered'],
            ['type' => AwardType::Mastery, 'isHardcore' => 0, 'label' => 'completed'],
            ['type' => AwardType::GameBeaten, 'isHardcore' => 1, 'label' => 'beaten-hardcore'],
            ['type' => AwardType::GameBeaten, 'isHardcore' => 0, 'label' => 'beaten-softcore'],
        ];

        foreach ($prestigeOrder as $prestigeOrderKind) {
            $found = $userAwards->first(function ($userAward) use ($prestigeOrderKind) {
                return
                    $userAward->award_type === $prestigeOrderKind['type']
                    && $userAward->award_data_extra === $prestigeOrderKind['isHardcore']
                ;
            });

            if ($found) {
                return [
                    'highestAwardKind' => $prestigeOrderKind['label'],
                    'highestAward' => $found,
                ];
            }
        }

        // If we land here, we didn't find anything.
        return null;
    }

    // == instance functions

    private function isGameRelated(): bool
    {
        return in_array($this->award_type, [AwardType::Mastery, AwardType::GameBeaten]);
    }

    // == accessors

    public function getGameAttribute(): ?Game
    {
        if ($this->isGameRelated()) {
            return $this->gameIfApplicable;
        }

        return null;
    }

    // == mutators

    // == relations

    /**
     * Warning: not all awards are associated with games!
     * see: PlayerBadge::getGameAttribute()
     *
     * @return BelongsTo<Game, $this>
     */
    public function gameIfApplicable(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'award_data', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->forGameId($game->id);
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeForGameId(Builder $query, int $gameId): Builder
    {
        $query->where(function ($query) {
            $query->where('award_type', AwardType::GameBeaten)
                ->orWhere('award_type', AwardType::Mastery);
        });

        $query->where('award_data', $gameId);

        return $query;
    }
}
