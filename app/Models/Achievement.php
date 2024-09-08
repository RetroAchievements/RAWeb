<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\HasAchievementCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;

class Achievement extends BaseModel implements HasComments
{
    /*
     * Community Traits
     */
    use HasAchievementCommunityFeatures;

    /*
     * Shared Traits
     */
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    use CausesActivity;
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO rename ID to id, remove getIdAttribute()
    // TODO rename Achievements table to achievements
    // TODO rename GameID column to game_id
    // TODO rename Title column to title, remove getTitleAttribute()
    // TODO rename Description column to description, remove getDescriptionAttribute()
    // TODO rename Points column to points, remove getPointsAttribute()
    // TODO rename TrueRation column to points_weighted, remove getPointsWeightedAttribute()
    // TODO rename unlocks_hardcore_total to unlocks_hardcore
    // TODO rename DateCreated to created_at, make non-nullable, remove getCreatedAtAttribute()
    // TODO rename Updated to updated_at, make non-nullable, remove getUpdatedAtAttribute()
    // TODO drop AssocVideo, move to guides or something
    // TODO drop MemAddr, migrate to triggerable morph
    // TODO drop Progress, ProgressMax, ProgressFormat migrate to triggerable morph
    // TODO drop Flags, derived from being included in an achievement set
    // TODO drop VotesPos, migrate to votable/ratable morph
    // TODO drop VotesNeg, migrate to votable/ratable morph
    // TODO drop BadgeName, derived from badge set
    // TODO drop DisplayOrder, derive from achievement_set_achievements.order_column
    protected $table = 'Achievements';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'DateCreated';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'BadgeName',
        'Description',
        'DisplayOrder',
        'Flags',
        'GameID',
        'Points',
        'Title',
        'type',
    ];

    protected $casts = [
        'DateModified' => 'datetime',
        'Flags' => 'integer',
        'GameID' => 'integer',
        'Points' => 'integer',
        'TrueRatio' => 'integer',
    ];

    protected $visible = [
        'BadgeName',
        'DateCreated',
        'DateModified',
        'Description',
        'DisplayOrder',
        'Flags',
        'GameID',
        'ID',
        'Points',
        'Title',
        'TrueRatio',
        'type',
        'user_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function (Achievement $achievement) {
            AchievementCreated::dispatch($achievement);
        });

        static::updated(function (Achievement $achievement) {
            if ($achievement->wasChanged('Points')) {
                AchievementPointsChanged::dispatch($achievement);
            }

            if ($achievement->wasChanged('type')) {
                AchievementTypeChanged::dispatch($achievement);
            }

            if ($achievement->wasChanged('Flags')) {
                if ($achievement->Flags === AchievementFlag::OfficialCore) {
                    AchievementPublished::dispatch($achievement);
                }

                if ($achievement->Flags === AchievementFlag::Unofficial) {
                    AchievementUnpublished::dispatch($achievement);
                }
            }

            if ($achievement->wasChanged('GameID')) {
                $originalGame = Game::find($achievement->getOriginal('GameID'));
                if ($originalGame) {
                    AchievementMoved::dispatch($achievement, $originalGame);
                }
            }
        });
    }

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

    // audit activity log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'AssocVideo',
                'BadgeName',
                'Description',
                'Flags',
                'GameID',
                'Points',
                'Title',
                'type',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == helpers

    public function unlockValidationHash(User $user, int $hardcore, int $offset = 0): string
    {
        $data = $this->id . $user->username . $hardcore . $this->id;
        if ($offset > 0) {
            $data .= $offset;
        }

        return md5($data);
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('achievement.show', [$this, $this->getSlugAttribute()]);
    }

    // TODO remove after rename
    public function getCreatedAtAttribute(): ?Carbon
    {
        return $this->attributes['DateCreated'] ? Carbon::parse($this->attributes['DateCreated']) : null;
    }

    public function getCanDelegateUnlocks(User $user): bool
    {
        return $this->game->getIsStandalone() && $this->user_id === $user->id;
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
        // TODO: read from media library

        return media_asset('Badge/' . $this->badge_name . '_lock.png');
    }

    public function getBadgeUnlockedUrlAttribute(): string
    {
        // TODO: read from media library

        return media_asset('Badge/' . $this->badge_name . '.png');
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

    // TODO remove after rename
    public function getBadgeNameAttribute(): string
    {
        return $this->attributes['BadgeName'];
    }

    public function getGameIdAttribute(): ?int
    {
        $gameId = $this->attributes['GameID'] ?? null;

        return $gameId ? (int) $gameId : null;
    }

    // TODO remove after rename
    public function getTitleAttribute(): ?string
    {
        return $this->attributes['Title'] ?? null;
    }

    // TODO remove after rename
    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['Description'] ?? null;
    }

    // TODO remove after rename
    public function getPointsAttribute(): int
    {
        return (int) $this->attributes['Points'];
    }

    // TODO remove after rename
    public function getUpdatedAtAttribute(): ?Carbon
    {
        return $this->attributes['Updated'] ? Carbon::parse($this->attributes['Updated']) : null;
    }

    // TODO remove after rename
    public function getPointsWeightedAttribute(): int
    {
        return (int) $this->attributes['TrueRatio'];
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<AchievementSet>
     */
    public function achievementSets(): BelongsToMany
    {
        return $this->belongsToMany(AchievementSet::class, 'achievement_set_achievements', 'achievement_id', 'achievement_set_id', 'ID', 'id')
            ->withPivot('order_column', 'created_at', 'updated_at');
    }

    /**
     * @return BelongsTo<User, Achievement>
     *
     * @deprecated make this multiple developers
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withTrashed();
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
    public function playerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_achievements', 'achievement_id', 'user_id')
            ->using(PlayerAchievement::class);
    }

    /**
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'achievement_id', 'ID');
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
    public function scopeMissable(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementType::Missable);
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
