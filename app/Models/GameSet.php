<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Actions\WriteGameSetSortTitleAction;
use App\Platform\Contracts\HasPermalink;
use App\Platform\Enums\GameSetRolePermission;
use App\Platform\Enums\GameSetType;
use App\Platform\Services\EventHubIdCacheService;
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
class GameSet extends BaseModel implements HasPermalink
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
        'sort_title',
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

        static::created(function ($gameSet) {
            if (str_contains($gameSet->title ?? '', 'Events -')) {
                EventHubIdCacheService::clearCache();
            }
        });

        static::updated(function ($gameSet) {
            if ($gameSet->isDirty('title')) {
                $oldTitle = $gameSet->getOriginal('title');
                $newTitle = $gameSet->title;

                // Clear the event hub ID cache if either the old or new title contains "Events -".
                if (str_contains($oldTitle ?? '', 'Events -') || str_contains($newTitle ?? '', 'Events -')) {
                    EventHubIdCacheService::clearCache();
                }
            }
        });

        static::saved(function (GameSet $gameSet) {
            $originalTitle = $gameSet->getOriginal('title');
            $freshGameSet = $gameSet->fresh();

            // Only update sort_title if there's actually a title.
            // SimilarGames sets don't have titles - they're just relationship containers.
            if ($freshGameSet->title !== null && ($originalTitle !== $freshGameSet->title || $gameSet->wasRecentlyCreated)) {
                (new WriteGameSetSortTitleAction())->execute(
                    $freshGameSet,
                    $freshGameSet->title,
                    shouldRespectCustomSortTitle: false,
                );
            }
        });

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

                $attachedGames = Game::whereIn('id', $pivotIds)
                    ->select(['id', 'title', 'system_id'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => $attachedGames
                        ->map(fn ($game) => [
                            'id' => $game->id,
                            'system_id' => $game->system_id,
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

                // Clear the event hub ID cache if we're attaching this hub to event hub parents.
                $eventHubParentIds = [self::CommunityEventsHubId, self::DeveloperEventsHubId];
                if (!empty(array_intersect($pivotIds, $eventHubParentIds))) {
                    EventHubIdCacheService::clearCache();
                }
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

                $detachedGames = Game::whereIn('id', $pivotIds)
                    ->select(['id', 'title', 'system_id'])
                    ->get();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => $detachedGames
                        ->map(fn ($game) => [
                            'id' => $game->id,
                            'system_id' => $game->system_id,
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

                // Clear the event hub ID cache if we're detaching this hub from event hub parents.
                $eventHubParentIds = [self::CommunityEventsHubId, self::DeveloperEventsHubId];
                if (!empty(array_intersect($pivotIds, $eventHubParentIds))) {
                    EventHubIdCacheService::clearCache();
                }
            }
        });
    }

    // == constants

    public const CentralHubId = 1;
    public const GenreSubgenreHubId = 2;
    public const SeriesHubId = 3;
    public const CommunityEventsHubId = 4;
    public const DeveloperEventsHubId = 5;
    public const FreePointsHubId = 3796;

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'has_mature_content',
                'image_asset_path',
                'internal_notes',
                'sort_title',
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

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    public function getIsEventHubAttribute(): bool
    {
        return in_array($this->id, EventHubIdCacheService::getEventHubIds());
    }

    public function getPermalinkAttribute(): string
    {
        return route('hub.show', $this);
    }

    public function getHasViewRoleRequirementAttribute(): bool
    {
        return $this->relationLoaded('viewRoles')
            ? $this->viewRoles->isNotEmpty()
            : $this->viewRoles()->exists();
    }

    public function getHasUpdateRoleRequirementAttribute(): bool
    {
        return $this->relationLoaded('updateRoles')
            ? $this->updateRoles->isNotEmpty()
            : $this->updateRoles()->exists();
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<Game, $this>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_set_games', 'game_set_id', 'game_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at', 'deleted_at');
    }

    /**
     * @return BelongsToMany<GameSet, $this>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'child_game_set_id', 'parent_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return BelongsToMany<GameSet, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'parent_game_set_id', 'child_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return BelongsTo<ForumTopic, $this>
     */
    public function forumTopic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function viewRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'game_set_roles')
            ->withTimestamps()
            ->wherePivot('permission', GameSetRolePermission::View->value);
    }

    /**
     * @return BelongsToMany<Role, $this>
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

    /**
     * @param Builder<GameSet> $query
     * @return Builder<GameSet>
     */
    public function scopeWithParentId(Builder $query, int $parentId): Builder
    {
        return $query->whereHas('parents', fn ($q) => $q->where('parent_game_set_id', $parentId));
    }

    /**
     * @param Builder<GameSet> $query
     * @return Builder<GameSet>
     */
    public function scopeTitleContains(Builder $query, string $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }
}
