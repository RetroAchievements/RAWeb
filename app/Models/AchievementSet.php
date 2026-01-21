<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\AchievementSetType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetFactory;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AchievementSet extends BaseModel
{
    /*
     * Shared Traits
     */
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }
    /** @use HasFactory<AchievementSetFactory> */
    use HasFactory;

    use PivotEventTrait;
    use SoftDeletes;

    // TODO add leaderboards() relation through achievement_set_leaderboards
    protected $table = 'achievement_sets';

    protected $fillable = [
        'user_id',
        'players_total',
        'players_hardcore',
        'achievements_first_published_at',
        'achievements_published',
        'achievements_unpublished',
        'points_total',
        'points_weighted',
        'image_asset_path',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'achievements_first_published_at' => 'datetime',
    ];

    protected static function newFactory(): AchievementSetFactory
    {
        return AchievementSetFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'incompatibleGameHashes') {
                /** @var User $user */
                $user = Auth::user();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => (new Collection($pivotIds))
                        ->map(fn ($pivotId) => [
                            'id' => $pivotId,
                            'attributes' => $pivotIdsAttributes[$pivotId],
                        ]),
                    ])
                    ->event('pivotAttached')
                    ->log('pivotAttached');
            }
        });

        static::pivotDetached(function ($model, $relationName, $pivotIds) {
            if ($relationName === 'incompatibleGameHashes') {
                /** @var User $user */
                $user = Auth::user();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => (new Collection($pivotIds))
                        ->map(fn ($pivotId) => [
                            'id' => $pivotId,
                        ]),
                    ])
                    ->withProperty('attributes', [$relationName => null])
                    ->event('pivotDetached')
                    ->log('pivotDetached');
            }
        });
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([

            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    public function getImageAssetPathUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    public function getTitleAttribute(): string
    {
        return $this->gameAchievementSets()->core()->first()->game->title;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<AchievementGroup, $this>
     */
    public function achievementGroups(): HasMany
    {
        return $this->hasMany(AchievementGroup::class)
            ->orderBy('order_column');
    }

    /**
     * @return HasMany<GameAchievementSet, $this>
     */
    public function gameAchievementSets(): HasMany
    {
        return $this->hasMany(GameAchievementSet::class);
    }

    /**
     * @return BelongsToMany<GameHash, $this>
     */
    public function incompatibleGameHashes(): BelongsToMany
    {
        return $this->belongsToMany(GameHash::class, 'achievement_set_incompatible_game_hashes')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'achievement_set_achievements', 'achievement_set_id', 'achievement_id', 'id', 'id')
            ->withPivot('order_column', 'achievement_group_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<AchievementSetAuthor, $this>
     */
    public function achievementSetAuthors(): HasMany
    {
        return $this->hasMany(AchievementSetAuthor::class, 'achievement_set_id', 'id');
    }

    /**
     * @return BelongsToMany<Game, $this>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_achievement_sets', 'achievement_set_id', 'game_id', 'id', 'id');
    }

    /**
     * Returns games linked to this achievement set, excluding legacy "subset backing games".
     * A backing game is one where the set is attached as type=core but also exists
     * as non-core on another game (the actual subset parent).
     *
     * @return BelongsToMany<Game, $this>
     */
    public function linkedGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_achievement_sets', 'achievement_set_id', 'game_id', 'id', 'id')
            ->where(function ($query) {
                $query->where('game_achievement_sets.type', '!=', AchievementSetType::Core->value)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('game_achievement_sets.type', AchievementSetType::Core->value)
                            ->whereNotExists(function ($existsQuery) {
                                $existsQuery->selectRaw('1')
                                    ->from('game_achievement_sets as gas_check')
                                    ->whereColumn('gas_check.achievement_set_id', 'game_achievement_sets.achievement_set_id')
                                    ->where('gas_check.type', '!=', AchievementSetType::Core->value);
                            });
                    });
            });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes

    // == helpers

    /**
     * Checks if this achievement set can be linked as a specialty type to the given game.
     * Specialty sets can only be linked to one parent game, so this returns false
     * if the set is already linked (as any non-core type) to a different game.
     */
    public function canBeLinkedAsSpecialtyTo(Game $game): bool
    {
        return $this->gameAchievementSets()
            ->where('type', '!=', AchievementSetType::Core->value)
            ->where('game_id', '!=', $game->id)
            ->doesntExist();
    }

    /**
     * Checks if this achievement set is already linked as a specialty type to any game
     * other than the given game.
     */
    public function isLinkedAsSpecialtyElsewhere(Game $game): bool
    {
        return $this->gameAchievementSets()
            ->whereIn('type', [
                AchievementSetType::Specialty->value,
                AchievementSetType::WillBeSpecialty->value,
            ])
            ->where('game_id', '!=', $game->id)
            ->exists();
    }
}
