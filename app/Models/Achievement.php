<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\HasAchievementCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Community\Enums\CommentableType;
use App\Platform\Contracts\HasPermalink;
use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\TicketableType;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementDeleted;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPromoted;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpromoted;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

// TODO implements HasComments

/**
 * @implements HasVersionedTrigger<Achievement>
 */
class Achievement extends BaseModel implements HasPermalink, HasVersionedTrigger
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
    use HasRelationships;

    use Searchable;
    use SoftDeletes;

    use CausesActivity;
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO drop game_id, achievements should be attached to achievement_sets, not games
    // TODO drop order_column, derive from achievement_set_achievements.order_column
    protected $table = 'achievements';

    protected $fillable = [
        'description',
        'embed_url',
        'game_id',
        'image_name',
        'is_promoted',
        'order_column',
        'points',
        'title',
        'trigger_definition',
        'trigger_id',
        'type',
        'user_id',
    ];

    protected $casts = [
        'modified_at' => 'datetime',
        'game_id' => 'integer',
        'is_promoted' => 'boolean',
        'points' => 'integer',
        'points_weighted' => 'integer',
        'unlock_percentage' => 'double',
        'unlock_hardcore_percentage' => 'double',
    ];

    protected $visible = [
        'created_at',
        'modified_at',
        'description',
        'embed_url',
        'game_id',
        'id',
        'image_name',
        'is_promoted',
        'order_column',
        'points',
        'points_weighted',
        'title',
        'type',
        'user_id',
    ];

    public const CLIENT_WARNING_ID = 101000001;

    /**
     * @deprecated only here for legacy API backwards compatibility
     */
    public const FLAG_PROMOTED = 3;
    /**
     * @deprecated only here for legacy API backwards compatibility
     */
    public const FLAG_UNPROMOTED = 5;

    public static function boot()
    {
        parent::boot();

        static::created(function (Achievement $achievement) {
            AchievementCreated::dispatch($achievement);
        });

        static::updated(function (Achievement $achievement) {
            if ($achievement->wasChanged('points')) {
                AchievementPointsChanged::dispatch($achievement);
            }

            if ($achievement->wasChanged('type')) {
                AchievementTypeChanged::dispatch($achievement);
            }

            if ($achievement->wasChanged('is_promoted')) {
                if ($achievement->is_promoted) {
                    AchievementPromoted::dispatch($achievement);
                } else {
                    AchievementUnpromoted::dispatch($achievement);
                }
            }

            if ($achievement->wasChanged('game_id')) {
                $originalGame = Game::find($achievement->getOriginal('game_id'));
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

        // When an achievement is deleted, dispatch an event so game metrics can be recalculated.
        // Otherwise, the denormalized unpublished achievement count will be wrong.
        static::deleted(function (Achievement $achievement) {
            AchievementDeleted::dispatch($achievement);
        });
    }

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }

    /**
     * Convert is_promoted boolean to the legacy Flags integer value.
     * For backwards compatibility with legacy code.
     */
    public function getFlagsAttribute(): int
    {
        return $this->is_promoted ? self::FLAG_PROMOTED : self::FLAG_UNPROMOTED;
    }

    /**
     * Convert a legacy Flags integer value to an is_promoted boolean.
     * For backwards compatibility with legacy code.
     */
    public static function isPromotedFromLegacyFlags(int $flags): ?bool
    {
        // Return null for invalid flag values (0, etc.) to skip filtering.
        // Only explicit 3 (promoted) or 5 (unpromoted) should apply a filter.
        if ($flags === self::FLAG_PROMOTED) {
            return true;
        }

        if ($flags === self::FLAG_UNPROMOTED) {
            return false;
        }

        return null;
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'embed_url',
                'game_id',
                'image_name',
                'is_promoted',
                'points',
                'title',
                'type',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'unlocks_total' => $this->unlocks_total,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_promoted;
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

    /**
     * Returns game IDs that are related to this achievement for multiset purposes.
     * For bonus sets: includes the base game.
     * For specialty/exclusive sets: only includes this achievement's game.
     *
     * @return int[]
     */
    public function getRelatedGameIds(): array
    {
        $achievementSet = $this->achievementSet;
        if (!$achievementSet) {
            return [$this->game_id];
        }

        $links = GameAchievementSet::where('achievement_set_id', $achievementSet->id)->get();
        if ($links->isEmpty()) {
            return [$this->game_id];
        }

        // Specialty and exclusive sets are isolated, so only their own game counts.
        if (
            $links->contains('type', AchievementSetType::Specialty)
            || $links->contains('type', AchievementSetType::Exclusive)
        ) {
            return [$this->game_id];
        }

        // For core and bonus sets, include all related games.
        return $links->pluck('game_id')->unique()->values()->toArray();
    }

    /**
     * Normalize smart quotes/apostrophes to ASCII equivalents.
     * Mobile devices often insert these characters which cause rendering issues in emulators.
     */
    private function normalizeSmartQuotes(string $value): string
    {
        return str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"],
            ["'", "'", '"', '"'],
            $value
        );
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('achievement.show', [$this, $this->getSlugAttribute()]);
    }

    public function getCanDelegateUnlocks(User $user): bool
    {
        return $this->game->getIsStandalone() && $this->user_id === $user->id;
    }

    public function getCanHaveBeatenTypes(): bool
    {
        // Non-game systems can't have beaten types.
        if (!System::isGameSystem($this->game?->system?->id ?? 0)) {
            return false;
        }

        // Check if achievement's sets are linked as non-core anywhere.
        $achievementSetIds = AchievementSetAchievement::where('achievement_id', $this->id)
            ->pluck('achievement_set_id');

        if ($achievementSetIds->isEmpty()) {
            // No sets yet, fall back to the game's title-based legacy check.
            return $this->game?->getCanHaveBeatenTypes() ?? true;
        }

        // If any set is linked as non-core, then the achievement can't have beaten types.
        $hasNonCoreLink = GameAchievementSet::whereIn('achievement_set_id', $achievementSetIds)
            ->where('type', '!=', AchievementSetType::Core)
            ->exists();

        return !$hasNonCoreLink;
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

        return media_asset('Badge/' . $this->image_name . '_lock.png');
    }

    public function getBadgeUnlockedUrlAttribute(): string
    {
        // TODO: read from media library

        return media_asset('Badge/' . $this->image_name . '.png');
    }

    // == mutators

    /**
     * @return Attribute<string, string>
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => $this->normalizeSmartQuotes($value),
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => $this->normalizeSmartQuotes($value),
        );
    }

    // == relations

    /**
     * @return HasMany<AchievementAuthor, $this>
     */
    public function authorshipCredits(): HasMany
    {
        return $this->hasMany(AchievementAuthor::class, 'achievement_id');
    }

    /**
     * @return HasOneThrough<AchievementSet, AchievementSetAchievement, $this>
     */
    public function achievementSet(): HasOneThrough
    {
        return $this->hasOneThrough(
            AchievementSet::class,
            AchievementSetAchievement::class,
            'achievement_id',
            'id',
            'id',
            'achievement_set_id'
        );
    }

    /**
     * @return BelongsTo<User, $this>
     *
     * @deprecated make this multiple developers
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Game, $this>
     *
     * @deprecated use games(), which goes through achievement sets. achievements.game_id will eventually be dropped.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Get all base games that include this achievement through achievement sets.
     * Excludes "subset backing games" by filtering out Core-type links when non-Core links exist.
     *
     * @return HasManyDeep<Game, $this>
     */
    public function games(): HasManyDeep
    {
        return $this->hasManyDeep(
            Game::class,
            [AchievementSetAchievement::class, AchievementSet::class, GameAchievementSet::class],
            ['achievement_id', 'id', 'achievement_set_id', 'id'],
            ['id', 'achievement_set_id', 'id', 'game_id']
        )->where(function ($query) {
            $query->where('game_achievement_sets.type', '!=', AchievementSetType::Core)
                ->orWhere(function ($q) {
                    $q->where('game_achievement_sets.type', AchievementSetType::Core)
                        ->whereNotExists(function ($sub) {
                            $sub->selectRaw('1')
                                ->from('game_achievement_sets as gas2')
                                ->whereColumn('gas2.achievement_set_id', 'game_achievement_sets.achievement_set_id')
                                ->where('gas2.type', '!=', AchievementSetType::Core);
                        });
                });
        });
    }

    /**
     * @deprecated use comments()
     * @return HasMany<Comment, $this>
     *
     * TODO use ->comments() after commentable_type and commentable_id are synced in Comments table
     */
    public function legacyComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id', 'id')
            ->where('commentable_type', CommentableType::Achievement);
    }

    /**
     * @return HasMany<AchievementMaintainer, $this>
     */
    public function maintainers(): HasMany
    {
        return $this->hasMany(AchievementMaintainer::class, 'achievement_id');
    }

    /**
     * @return HasOne<AchievementMaintainer, $this>
     */
    public function activeMaintainer(): HasOne
    {
        return $this->hasOne(AchievementMaintainer::class, 'achievement_id')
            ->where('is_active', true);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function playerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_achievements', 'achievement_id', 'user_id')
            ->using(PlayerAchievement::class);
    }

    /**
     * @return HasMany<PlayerAchievement, $this>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'achievement_id');
    }

    /**
     * @return HasMany<EventAchievement, $this>
     */
    public function eventAchievements(): HasMany
    {
        return $this->hasMany(EventAchievement::class, 'source_achievement_id');
    }

    /**
     * @return HasOne<EventAchievement, $this>
     */
    public function eventData(): HasOne
    {
        return $this->hasOne(EventAchievement::class, 'achievement_id');
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id')->where('commentable_type', CommentableType::Achievement);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticketable_id')
            ->where('ticketable_type', TicketableType::Achievement);
    }

    /**
     * @return BelongsTo<Trigger, $this>
     */
    public function currentTrigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'id');
    }

    /**
     * @return MorphOne<Trigger, $this>
     */
    public function trigger(): MorphOne
    {
        return $this->morphOne(Trigger::class, 'triggerable')
            ->latest('version');
    }

    /**
     * @return MorphMany<Trigger, $this>
     */
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
    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', true);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeUnpromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', false);
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
            $join->on('player_achievements.achievement_id', '=', 'achievements.id');
            $join->where(DB::raw('player_achievements.user_id'), '=', $user->id);
        });
        $query->addSelect('achievements.*');
        $query->addSelect('player_achievements.unlocked_at');
        $query->addSelect('player_achievements.unlocked_hardcore_at');
        $query->addSelect(DB::raw('player_achievements.id as player_achievement_id'));

        return $query;
    }

    /**
     * achievements -> achievement_set_achievements -> game_achievement_sets
     *
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeForGame(Builder $query, int $gameId): Builder
    {
        return $query->whereExists(function ($subQuery) use ($gameId) {
            $subQuery->select(DB::raw(1))
                ->from('achievement_set_achievements')
                ->join('game_achievement_sets', 'achievement_set_achievements.achievement_set_id', '=', 'game_achievement_sets.achievement_set_id')
                ->whereColumn('achievement_set_achievements.achievement_id', 'achievements.id')
                ->where('game_achievement_sets.game_id', $gameId);
        });
    }

    /**
     * Supports tri-state: 'true' (promoted only), 'false' (unpromoted only), and 'all' (no filter).
     *
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeWithPromotedStatus(Builder $query, string $value): Builder
    {
        if ($value === 'all') {
            return $query;
        }

        $isPromoted = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($isPromoted !== null) {
            return $query->where('is_promoted', $isPromoted);
        }

        return $query;
    }
}
