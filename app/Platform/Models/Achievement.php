<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Concerns\HasAchievementCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Achievement extends BaseModel implements HasComments
{
    /*
     * Community Traits
     */
    use HasAchievementCommunityFeatures;

    /*
     * Shared Traits
     */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    // TODO rename Achievements table to achievements
    // TODO rename GameID column to game_id
    // TODO rename Title column to title
    // TODO rename Description column to description
    // TODO rename Points column to points
    // TODO drop AssocVideo, move to guides or something
    // TODO rename TrueRation column to points_weighted
    // TODO rename unlocks_hardcore_total to unlocks_hardcore
    // TODO drop MemAddr, migrate to triggerable morph
    // TODO drop Progress, ProgressMax, ProgressFormat migrate to triggerable morph
    // TODO drop Flags, derived from being included in an achievement set
    // TODO drop Author, migrate to user_id
    // TODO drop VotesPos, migrate to votable/ratable morph
    // TODO drop VotesNeg, migrate to votable/ratable morph
    // TODO drop BadgeName, derived from badge set
    protected $table = 'Achievements';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'DateCreated';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'Title',
        'Description',
    ];

    protected $casts = [
        'DateModified' => 'datetime',
        'Points' => 'integer',
        'TrueRatio' => 'integer',
        'Flags' => 'integer',
    ];

    protected $visible = [
        'ID',
        'GameID',
        'BadgeName',
        'Title',
        'Description',
        'Points',
        'TrueRatio',
        'Author',
        'DateCreated',
        'DateModified',
        'type',
        'DisplayOrder',
    ];

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }

    // search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return $this->isPublished();
        // TODO return true;
        return false;
    }

    // == helpers

    public function unlockValidationHash(User $user, int $hardcore): string
    {
        return md5($this->id . $user->username . $hardcore . $this->id);
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('achievement.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('achievement.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    public function getBadgeUrlAttribute(): string
    {
        return $this->getBadgeUnlockedUrlAttribute();
    }

    public function getBadgeLockedUrlAttribute(): string
    {
        /**
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->badge_name . '_lock.png';
        // if (!file_exists(public_path($badge))) {
        $badge = 'assets/images/achievement/badge-locked.png';

        // }
        return $badge;
    }

    public function getBadgeUnlockedUrlAttribute(): string
    {
        /**
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->badge_name . '.png';
        // if (!file_exists(public_path($badge))) {
        $badge = 'assets/images/achievement/badge.png';

        // }
        return $badge;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->Flags === AchievementFlag::OfficialCore;
    }

    // TODO remove after rename

    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    public function getGameIdAttribute(): int
    {
        return $this->attributes['GameID'];
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['Title'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['Description'] ?? null;
    }

    public function getPointsAttribute(): int
    {
        return (int) $this->attributes['Points'];
    }

    public function getPointsWeightedAttribute(): int
    {
        return (int) $this->attributes['TrueRatio'];
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Achievement>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsTo<User, Achievement>
     *
     * @deprecated make this multiple developers
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Author', 'User');
    }

    /**
     * @return BelongsTo<Game, Achievement>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'Awarded', 'AchievementID', 'User')
            ->using(PlayerAchievementLegacy::class);
    }

    /**
     * @return HasMany<PlayerAchievementLegacy>
     */
    public function playerAchievementsLegacy(): HasMany
    {
        return $this->hasMany(PlayerAchievementLegacy::class);
    }

    /**
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }

    /**
     * Return unlocks separated by unlock mode; both softcore and hardcore in "raw" form
     *
     * @return HasMany<PlayerAchievementLegacy>
     */
    public function rawUnlocks(): HasMany
    {
        return $this->hasMany(PlayerAchievementLegacy::class, 'AchievementID');
    }

    /**
     * Merge softcore with hardcore entries if the unlock mode is not specified
     *
     * @return HasMany<PlayerAchievementLegacy>
     */
    public function unlocks(?int $mode = null): HasMany
    {
        if ($mode !== null) {
            return $this->rawUnlocks()->where('HardcoreMode', $mode);
        }

        return $this->rawUnlocks()->selectRaw('AchievementID, User, MAX(Date) Date, MAX(HardcoreMode) HardcoreMode')
            ->groupBy(['AchievementID', 'User']);
    }

    // == scopes

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeFlag(Builder $query, int $flag): Builder
    {
        return $query->where('Flags', $flag);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $this->scopeFlag($query, AchievementFlag::OfficialCore);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeUnpublished(Builder $query): Builder
    {
        return $this->scopeFlag($query, AchievementFlag::Unofficial);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeType(Builder $query, string|array $type): Builder
    {
        return $query->whereIn('type', Arr::wrap($type));
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeProgression(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementType::Progression);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeWinCondition(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementType::WinCondition);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeWithUnlocksByUser(Builder $query, User $user): Builder
    {
        $query->leftJoin('player_achievements', function ($join) use ($user) {
            $join->on('player_achievements.achievement_id', '=', 'Achievements.ID');
            $join->where('player_achievements.user_id', '=', $user->id);
        });
        $query->addSelect('Achievements.*');
        $query->addSelect('player_achievements.unlocked_at');
        $query->addSelect('player_achievements.unlocked_hardcore_at');
        $query->addSelect(DB::raw('player_achievements.id as player_achievement_id'));

        return $query;
    }
}
