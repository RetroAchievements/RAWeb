<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerBadgeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'award_key',
        'award_tier',
        'display_award_tier',
        'awarded_at',
        'order_column',
    ];

    protected $casts = [
        'award_type' => AwardType::class,
        'award_key' => 'int',
        'award_tier' => 'int',
        'display_award_tier' => 'int',
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

    private const MEDIA_CONTRIBUTION_BOUNDARIES = [
        10,
        30,
        100,
        300,
        1000,
        3000,
    ];

    private static function getThresholds(AwardType $awardType): ?array
    {
        return match ($awardType) {
            AwardType::AchievementUnlocksYield => self::DEVELOPER_COUNT_BOUNDARIES,
            AwardType::AchievementPointsYield => self::DEVELOPER_POINT_BOUNDARIES,
            AwardType::MediaContribution => self::MEDIA_CONTRIBUTION_BOUNDARIES,
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
                    && $userAward->award_tier === $prestigeOrderKind['isHardcore']
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

    public function isGameRelated(): bool
    {
        return in_array($this->award_type, [AwardType::Mastery, AwardType::GameBeaten], true);
    }

    public function isVisibleOnUserProfile(): bool
    {
        return $this->order_column !== -1;
    }

    public function isSiteEventAward(): bool
    {
        return
            $this->award_type === AwardType::Event
            && (bool) $this->eventIfApplicable?->gives_site_award;
    }

    public function isCountedAsEventAward(): bool
    {
        if ($this->award_type === AwardType::Event) {
            return !$this->isSiteEventAward();
        }

        return
            $this->isGameRelated()
            && $this->gameIfApplicable?->system_id === System::Events;
    }

    public function isCountedAsSiteAward(): bool
    {
        if ($this->award_type === AwardType::Event) {
            return $this->isSiteEventAward();
        }

        return in_array($this->award_type, [
            AwardType::AchievementUnlocksYield,
            AwardType::AchievementPointsYield,
            AwardType::PatreonSupporter,
            AwardType::CertifiedLegend,
            AwardType::Playtest,
            AwardType::MediaContribution,
        ], true);
    }

    // == accessors

    public function getGameAttribute(): ?Game
    {
        if ($this->isGameRelated()) {
            return $this->gameIfApplicable;
        }

        return null;
    }

    /**
     * The tier index the user has chosen to display, falling back to the earned tier
     * (award_tier) when no preference is set.
     */
    public function getDisplayedTierAttribute(): int
    {
        return $this->display_award_tier ?? (int) $this->award_tier;
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
        return $this->belongsTo(Game::class, 'award_key', 'id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function eventIfApplicable(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'award_key', 'id');
    }

    /**
     * @return BelongsTo<SiteAward, $this>
     */
    public function siteAwardIfApplicable(): BelongsTo
    {
        return $this->belongsTo(SiteAward::class, 'award_key', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeAwardedFrom(Builder $query, string $date): Builder
    {
        return $query->where('awarded_at', '>=', $date);
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeAwardedTo(Builder $query, string $date): Builder
    {
        // A bare date is meant to include the whole final day. We'll compare strictly
        // below the next day's midnight. A literal <= against a bare date would
        // silently exclude everything after midnight on that day.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $startOfNextDay = Carbon::createFromFormat('Y-m-d', $date)->addDay()->startOfDay();

            return $query->where('awarded_at', '<', $startOfNextDay);
        }

        return $query->where('awarded_at', '<=', $date);
    }

    /**
     * Select the canonical award rows used by public API/profile rendering.
     * This collapses superseded casual game awards and prior developer tiers.
     *
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeCanonicalForApiUser(Builder $query, int $userId): Builder
    {
        $collapsedTypes = [
            AwardType::AchievementUnlocksYield->value,
            AwardType::AchievementPointsYield->value,
            AwardType::PatreonSupporter->value,
            AwardType::CertifiedLegend->value,
            AwardType::MediaContribution->value,
        ];
        $gameTypes = AwardType::gameValues();

        $partitionKeyExpression = sprintf(
            'CASE WHEN award_type IN (%s) THEN 0 ELSE award_key END',
            implode(', ', array_fill(0, count($collapsedTypes), '?'))
        );
        $priorityExpression = sprintf(
            'CASE WHEN award_type IN (%s) THEN award_tier ELSE award_key END',
            implode(', ', array_fill(0, count($gameTypes), '?'))
        );

        $rankedAwards = static::query()
            ->select('id')
            ->selectRaw(
                "ROW_NUMBER() OVER (
                    PARTITION BY award_type, {$partitionKeyExpression}
                    ORDER BY {$priorityExpression} DESC, awarded_at DESC, id DESC
                ) as row_num",
                array_merge($collapsedTypes, $gameTypes),
            )
            ->where('user_id', $userId);

        return $query->whereIn($this->qualifyColumn('id'), function ($subquery) use ($rankedAwards) {
            $subquery->fromSub($rankedAwards->toBase(), 'ranked_awards')
                ->select('id')
                ->where('row_num', 1);
        });
    }

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

        $query->where('award_key', $gameId);

        return $query;
    }

    /**
     * Keep only the highest progression award per user/game while leaving non-game awards untouched.
     *
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeHighestGameAwardPerGame(Builder $query): Builder
    {
        $gameTypes = AwardType::gameValues();
        $rankedGameAwards = (clone $query)
            ->reorder()
            ->select($this->qualifyColumn('id'))
            ->selectRaw(
                "ROW_NUMBER() OVER (
                    PARTITION BY {$this->qualifyColumn('user_id')}, {$this->qualifyColumn('award_key')}
                    ORDER BY {$this->gameAwardPriorityExpression($this->getTable())} DESC,
                        {$this->qualifyColumn('awarded_at')} DESC,
                        {$this->qualifyColumn('id')} DESC
                ) as row_num",
                $this->gameAwardPriorityBindings(),
            )
            ->whereIn($this->qualifyColumn('award_type'), $gameTypes);

        return $query->where(function (Builder $query) use ($gameTypes, $rankedGameAwards) {
            $query
                ->whereNotIn($this->qualifyColumn('award_type'), $gameTypes)
                ->orWhereIn($this->qualifyColumn('id'), function ($subquery) use ($rankedGameAwards) {
                    $subquery->fromSub($rankedGameAwards->toBase(), 'ranked_game_awards')
                        ->select('id')
                        ->where('row_num', 1);
                });
        });
    }

    private function gameAwardPriorityExpression(string $tableAlias): string
    {
        return <<<SQL
            CASE
                WHEN {$tableAlias}.award_type = ? AND {$tableAlias}.award_tier = ? THEN 4
                WHEN {$tableAlias}.award_type = ? AND {$tableAlias}.award_tier = ? THEN 3
                WHEN {$tableAlias}.award_type = ? AND {$tableAlias}.award_tier = ? THEN 2
                WHEN {$tableAlias}.award_type = ? AND {$tableAlias}.award_tier = ? THEN 1
                ELSE 0
            END
        SQL;
    }

    /**
     * @return list<int|string>
     */
    private function gameAwardPriorityBindings(): array
    {
        return [
            AwardType::Mastery->value, UnlockMode::Hardcore, // mastery
            AwardType::Mastery->value, UnlockMode::Casual, // completion
            AwardType::GameBeaten->value, UnlockMode::Hardcore, // beaten hardcore
            AwardType::GameBeaten->value, UnlockMode::Casual, // beaten casual
        ];
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeForEventId(Builder $query, int $eventId): Builder
    {
        return $query
            ->where('award_type', AwardType::Event)
            ->where('award_key', $eventId);
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeOrderedForProfile(Builder $query): Builder
    {
        return $query
            ->orderBy('order_column')
            ->orderBy('awarded_at')
            ->orderBy('award_type')
            ->orderBy('award_tier');
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function scopeVisibleOnUserProfile(Builder $query): Builder
    {
        return $query->where('order_column', '!=', -1);
    }
}
