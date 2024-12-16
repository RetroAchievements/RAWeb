<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameSetType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameSetFactory;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

// TODO drop image_asset_path, migrate to media
class GameSet extends BaseModel
{
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }
    /** @use HasFactory<GameSetFactory> */
    use HasFactory;

    use PivotEventTrait;
    use SoftDeletes;

    protected $table = 'game_sets';

    protected $fillable = [
        'definition',
        'game_id',
        'internal_notes',
        'image_asset_path',
        'title',
        'type',
        'updated_at',
        'user_id',
    ];

    protected $casts = [
        'type' => GameSetType::class,
    ];

    protected static function newFactory(): GameSetFactory
    {
        return GameSetFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'games') {
                /** @var User $user */
                $user = Auth::user();

                $attachedGames = Game::whereIn('ID', $pivotIds)
                    ->select(['ID', 'Title', 'ConsoleID'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => $attachedGames
                        ->map(fn ($game) => [
                            'id' => $game->ID,
                            'system_id' => $game->ConsoleID,
                            'title' => $game->title,
                        ]),
                    ])
                    ->event('pivotAttached')
                    ->log('pivotAttached');
            }

            if ($relationName === 'parents') {
                /** @var User $user */
                $user = Auth::user();

                $attachedParents = GameSet::whereIn('id', $pivotIds)
                    ->select(['id', 'title'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName = $attachedParents
                        ->map(fn ($gameSet) => [
                            'id' => $gameSet->id,
                            'title' => $gameSet->title,
                        ]),
                    ])
                    ->event('pivotAttached')
                    ->log('pivotAttached');
            }
        });

        static::pivotDetached(function ($model, $relationName, $pivotIds) {
            if ($relationName === 'games') {
                /** @var User $user */
                $user = Auth::user();

                $detachedGames = Game::whereIn('ID', $pivotIds)
                    ->select(['ID', 'Title', 'ConsoleID'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => $detachedGames
                        ->map(fn ($game) => [
                            'id' => $game->ID,
                            'system_id' => $game->ConsoleID,
                            'title' => $game->title,
                        ]),
                    ])
                    ->withProperty('attributes', [$relationName => null])
                    ->event('pivotDetached')
                    ->log('pivotDetached');
            }

            if ($relationName === 'parents') {
                /** @var User $user */
                $user = Auth::user();

                $detachedParents = GameSet::whereIn('id', $pivotIds)
                    ->select(['id', 'title'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => $detachedParents
                        ->map(fn ($gameSet) => [
                            'id' => $gameSet->id,
                            'title' => $gameSet->title,
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
                'title',
                'internal_notes',
                'image_asset_path',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == constants

    public const CentralHubId = 1;
    public const GenreSubgenreHubId = 2;
    public const SeriesHubId = 3;
    public const CommunityEventsHubId = 4;
    public const DeveloperEventsHubId = 5;

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    public function getPermalinkAttribute(): string
    {
        return route('hub.show', $this);
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, GameSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_set_games', 'game_set_id', 'game_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at', 'deleted_at');
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'child_game_set_id', 'parent_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'parent_game_set_id', 'child_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    // == scopes

    /**
     * @param Builder<GameSet> $query
     * @return Builder<GameSet>
     */
    public function scopeCentralHub(Builder $query): Builder
    {
        return $query->whereId(self::CentralHubId);
    }
}
