<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\HasAchievementCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Community\Enums\ArticleType;
use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Enums\AchievementAuthorTask;
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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;

// TODO implements HasComments

/**
 * @implements HasVersionedTrigger<Achievement>
 */
class Achievement extends BaseModel implements HasVersionedTrigger
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
        'MemAddr',
        'user_id',
        'trigger_id',
    ];

    // TODO cast Flags to AchievementFlag if it isn't dropped from the table
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
                if ($achievement->Flags === AchievementFlag::OfficialCore->value) {
                    AchievementPublished::dispatch($achievement);
                }

                if ($achievement->Flags === AchievementFlag::Unofficial->value) {
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

        static::deleting(function (Achievement $achievement) {
            // If we're force deleting the achievement, force delete the tickets.
            if ($achievement->isForceDeleting()) {
                $achievement->tickets()->forceDelete();

                return;
            }

            // Otherwise, soft delete the tickets.
            $achievement->tickets()->delete();
        });

        // When restoring an achievement, restore its tickets.
        static::restoring(function (Achievement $achievement) {
            $achievement->tickets()->restore();
        });
    }

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }

    public const CLIENT_WARNING_ID = 101000001;

    // == logging

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

    // == search

    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->ID,
            'title' => $this->title,
            'description' => $this->description,
            'unlocks_total' => $this->unlocks_total,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        if ($this->Flags !== AchievementFlag::OfficialCore->value) {
            return false;
        }

        return true;
    }

    // == helpers

    public function ensureAuthorshipCredit(User $user, AchievementAuthorTask $task, ?Carbon $backdate = null): AchievementAuthor
    {
        return $this->authorshipCredits()->firstOrCreate(
            ['user_id' => $user->id, 'task' => $task->value],
            ['created_at' => $backdate ?? now(), 'updated_at' => now()]
        );
    }

    public function getMaintainerAt(Carbon $timestamp): ?User
    {
        $maintainer = $this->maintainers()
            ->where('effective_from', '<=', $timestamp)
            ->where(function ($query) use ($timestamp) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>', $timestamp);
            })
            ->first();

        return $maintainer ? $maintainer->user : $this->developer;
    }

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
        return $this->Flags === AchievementFlag::OfficialCore->value;
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
     * @return HasMany<AchievementAuthor>
     */
    public function authorshipCredits(): HasMany
    {
        return $this->hasMany(AchievementAuthor::class, 'achievement_id', 'ID');
    }

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
     * @deprecated use comments()
     * @return HasMany<Comment>
     *
     * TODO use ->comments() after commentable_type and commentable_id are synced in Comments table
     */
    public function legacyComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID', 'ID')
            ->where('ArticleType', ArticleType::Achievement);
    }

    /**
     * @return HasMany<AchievementMaintainer>
     */
    public function maintainers(): HasMany
    {
        return $this->hasMany(AchievementMaintainer::class, 'achievement_id', 'ID');
    }

    /**
     * @return HasOne<AchievementMaintainer>
     */
    public function activeMaintainer(): HasOne
    {
        return $this->hasOne(AchievementMaintainer::class, 'achievement_id', 'ID')
            ->where('is_active', true);
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

    /**
     * @return HasMany<EventAchievement>
     */
    public function eventAchievements(): HasMany
    {
        return $this->hasMany(EventAchievement::class, 'source_achievement_id', 'ID');
    }

    /**
     * @return HasOne<EventAchievement>
     */
    public function eventData(): HasOne
    {
        return $this->hasOne(EventAchievement::class, 'achievement_id');
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::Achievement);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return HasMany<Ticket>
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'AchievementID');
    }

    /**
     * @return BelongsTo<Trigger, Achievement>
     */
    public function currentTrigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'ID');
    }

    public function trigger(): MorphOne
    {
        return $this->morphOne(Trigger::class, 'triggerable')
            ->latest('version');
    }

    public function triggers(): MorphMany
    {
        return $this->morphMany(Trigger::class, 'triggerable')
            ->orderBy('version');
    }

    // == scopes

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeFlag(Builder $query, AchievementFlag $flag): Builder
    {
        return $query->where('Flags', $flag->value);
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
