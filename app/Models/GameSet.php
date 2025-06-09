<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameSetRolePermission;
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
use Laravel\Scout\Searchable;
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
    use Searchable;
    use SoftDeletes;

    protected $table = 'game_sets';

    protected $fillable = [
        'definition',
        'game_id',
        'forum_topic_id',
        'internal_notes',
        'image_asset_path',
        'has_mature_content',
        'title',
        'type',
        'updated_at',
        'user_id',
    ];

    protected $casts = [
        'has_mature_content' => 'boolean',
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
            if ($relationName === 'viewRoles' || $relationName === 'updateRoles') {
                /** @var User $user */
                $user = Auth::user();

                $attachedRoles = Role::whereIn('id', $pivotIds)
                    ->select(['id', 'name'])
                    ->get();

                $permission = $relationName === 'viewRoles'
                    ? GameSetRolePermission::View->value
                    : GameSetRolePermission::Update->value;

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => $attachedRoles
                        ->map(fn ($role) => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permission' => $permission,
                        ]),
                    ])
                    ->event('pivotAttached')
                    ->log('pivotAttached');
            }

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

                // Log the attachment on each game model.
                foreach ($attachedGames as $game) {
                    $attribute = $model->type === GameSetType::Hub ? 'hubs' : 'similarGames';

                    activity()->causedBy($user)->performedOn($game)
                        ->withProperty('old', [$attribute => null])
                        ->withProperty('attributes', [$attribute => [
                            'id' => $model->id,
                            'title' => $model->title,
                        ]])
                        ->event('pivotAttached')
                        ->log('pivotAttached');
                }
            }

            if ($relationName === 'parents') {
                /** @var User $user */
                $user = Auth::user();

                $attachedParents = GameSet::whereIn('id', $pivotIds)
                    ->select(['id', 'title'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => $attachedParents
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
            if ($relationName === 'viewRoles' || $relationName === 'updateRoles') {
                /** @var User $user */
                $user = Auth::user();

                $detachedRoles = Role::whereIn('id', $pivotIds)
                    ->select(['id', 'name'])
                    ->get();

                $permission = $relationName === 'viewRoles'
                    ? GameSetRolePermission::View->value
                    : GameSetRolePermission::Update->value;

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => $detachedRoles
                        ->map(fn ($role) => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'permission' => $permission,
                        ]),
                    ])
                    ->withProperty('attributes', [$relationName => null])
                    ->event('pivotDetached')
                    ->log('pivotDetached');
            }

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

                // Log the detachment on each game model.
                foreach ($detachedGames as $game) {
                    $attribute = $model->type === GameSetType::Hub ? 'hubs' : 'similarGames';

                    activity()->causedBy($user)->performedOn($game)
                        ->withProperty('old', [$attribute => [
                            'id' => $model->id,
                            'title' => $model->title,
                        ]])
                        ->withProperty('attributes', [$attribute => null])
                        ->event('pivotDetached')
                        ->log('pivotDetached');
                }
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
                'has_mature_content',
                'image_asset_path',
                'internal_notes',
                'title',
                'viewRoles',
                'updateRoles',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        $this->loadCount('games');

        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'games_count' => $this->games_count,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->type === GameSetType::Hub;
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

    public function getIsEventHubAttribute(): bool
    {
        $eventHubIds = [self::CommunityEventsHubId, self::DeveloperEventsHubId];

        return $this->children->contains(function ($child) use ($eventHubIds) {
            return in_array($child->id, $eventHubIds) || str_contains($child->title, 'Events -');
        });
    }

    public function getPermalinkAttribute(): string
    {
        return route('hub.show', $this);
    }

    public function getHasViewRoleRequirementAttribute(): bool
    {
        return $this->viewRoles()->exists();
    }

    public function getHasUpdateRoleRequirementAttribute(): bool
    {
        return $this->updateRoles()->exists();
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

    /**
     * @return BelongsTo<ForumTopic, GameSet>
     */
    public function forumTopic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class);
    }

    /**
     * @return BelongsToMany<Role>
     */
    public function viewRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'game_set_roles')
            ->withTimestamps()
            ->wherePivot('permission', GameSetRolePermission::View->value);
    }

    /**
     * @return BelongsToMany<Role>
     */
    public function updateRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'game_set_roles')
            ->withTimestamps()
            ->wherePivot('permission', GameSetRolePermission::Update->value);
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
