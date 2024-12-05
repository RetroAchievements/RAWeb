<?php

declare(strict_types=1);

namespace App\Models;

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
    use LogsActivity { LogsActivity::activities as auditLog; }
    /** @use HasFactory<AchievementSetFactory> */
    use HasFactory;

    use PivotEventTrait;
    use SoftDeletes;

    protected $table = 'achievement_sets';

    protected $fillable = [
        'user_id',
        'players_total',
        'players_hardcore',
        'achievements_published',
        'achievements_unpublished',
        'points_total',
        'points_weighted',
        'created_at',
        'updated_at',
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

    public function getTitleAttribute(): string
    {
        return $this->gameAchievementSets()->core()->first()->game->title;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<GameAchievementSet>
     */
    public function gameAchievementSets(): HasMany
    {
        return $this->hasMany(GameAchievementSet::class);
    }

    /**
     * @return BelongsToMany<GameHash>
     */
    public function incompatibleGameHashes(): BelongsToMany
    {
        return $this->belongsToMany(GameHash::class, 'achievement_set_incompatible_game_hashes')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Achievement>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'achievement_set_achievements', 'achievement_set_id', 'achievement_id', 'id', 'ID')
            ->withPivot('order_column')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_achievement_sets', 'achievement_set_id', 'game_id', 'id', 'ID');
    }

    /**
     * @return BelongsTo<User, AchievementSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
