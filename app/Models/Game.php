<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Community\Concerns\HasGameCommunityFeatures;
use App\Community\Enums\ArticleType;
use App\Enums\GameHashCompatibility;
use App\Platform\Actions\ComputeGameSearchTitlesAction;
use App\Platform\Actions\SyncAchievementSetImageAssetPathFromGameAction;
use App\Platform\Actions\SyncGameTagsFromTitleAction;
use App\Platform\Actions\WriteGameSortTitleFromGameTitleAction;
use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\ReleasedAtGranularity;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameFactory;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

// TODO implements HasComments

/**
 * @implements HasVersionedTrigger<Game>
 */
class Game extends BaseModel implements HasMedia, HasVersionedTrigger
{
    /*
     * Community Traits
     */
    use DiscussedInForum;
    use HasGameCommunityFeatures;

    /*
     * Shared Traits
     */
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }
    /** @use HasFactory<GameFactory> */
    use HasFactory;
    use HasTags;
    use InteractsWithMedia;

    use PivotEventTrait;
    use Searchable;
    use SoftDeletes;

    // TODO migrate forum_topic_id to forumable morph
    // TODO migrate image_*_asset_path columns to media library
    // TODO drop achievement_set_version_hash, migrate to achievement_sets
    protected $table = 'games';

    protected $fillable = [
        'title',
        'sort_title',
        'system_id',
        'forum_topic_id',
        'publisher',
        'developer',
        'genre',
        'released_at',
        'released_at_granularity',
        'trigger_id',
        'legacy_guide_url',
        'comments_locked_at',
        'image_icon_asset_path',
        'image_title_asset_path',
        'image_ingame_asset_path',
        'image_box_art_asset_path',
    ];

    protected $casts = [
        'comments_locked_at' => 'datetime',
        'last_achievement_update' => 'datetime',
        'released_at_granularity' => ReleasedAtGranularity::class,
        'released_at' => 'datetime',
    ];

    protected $visible = [
        'id',
        'title',
        'sort_title',
        'system_id',
        'forum_topic_id',
        'image_icon_asset_path',
        'image_title_asset_path',
        'image_ingame_asset_path',
        'image_box_art_asset_path',
        'publisher',
        'developer',
        'genre',
        'released_at',
        'released_at_granularity',
        'trigger_definition',
        'legacy_guide_url',
        'updated_at',
        'achievement_set_version_hash',
        'achievements_published',
        'points_total',
        'players_total',
    ];

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function (Game $game) {
            $originalTitle = $game->getOriginal('title');
            $originalImageIcon = $game->getOriginal('image_icon_asset_path');
            $freshGame = $game->fresh(); // $game starts with stale values.

            // Handle game title changes.
            if ($originalTitle !== $freshGame->title || $game->wasRecentlyCreated) {
                // Always refresh the sort title on a game title change.
                (new WriteGameSortTitleFromGameTitleAction())->execute(
                    $freshGame,
                    $freshGame->title,
                    shouldRespectCustomSortTitle: false,
                );

                // Double write to the taggables table to keep structured
                // tags (ie: "~Hack~", "~Homebrew~", etc) in sync.
                (new SyncGameTagsFromTitleAction())->execute(
                    $freshGame,
                    $originalTitle,
                    $freshGame->title
                );

                // Update the canonical title in game_releases.
                if (!$game->wasRecentlyCreated) {
                    $canonicalTitle = $freshGame->releases()->where('is_canonical_game_title', true)->first();
                    if ($canonicalTitle) {
                        $canonicalTitle->title = $freshGame->title;
                        $canonicalTitle->save();
                    }
                }
            }

            // Handle game badge changes.
            if ($originalImageIcon !== $freshGame->image_icon_asset_path) {
                (new SyncAchievementSetImageAssetPathFromGameAction())->execute($freshGame);
            }

            // Keep game_sets in sync (only for title changes, not new "hub games").
            if ($originalTitle !== $freshGame->title && $game->system_id === System::Hubs) {
                $foundGameSet = GameSet::whereType(GameSetType::Hub)
                    ->whereGameId($game->id)
                    ->first();

                if ($foundGameSet) {
                    $foundGameSet->title = $freshGame->title;
                    $foundGameSet->save();
                }
            }

            // Sync game achievement set titles when subset (backing) game titles change.
            if ($originalTitle !== $freshGame->title && str_contains($freshGame->title, '[Subset -')) {
                if (preg_match('/\[Subset\s*-\s*(.+?)\]/', $freshGame->title, $matches)) {
                    $newSubsetTitle = trim($matches[1]);

                    // Find this game's core achievement set.
                    $foundCoreSet = GameAchievementSet::where('game_id', $freshGame->id)
                        ->where('type', AchievementSetType::Core)
                        ->first();

                    if ($foundCoreSet) {
                        // Find where this achievement set is attached as non-core to another game.
                        GameAchievementSet::where('achievement_set_id', $foundCoreSet->achievement_set_id)
                            ->where('type', '!=', AchievementSetType::Core)
                            ->update(['title' => $newSubsetTitle]);
                    }
                }
            }
        });

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'achievementSets') {
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
            if ($relationName === 'achievementSets') {
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

    // TODO log game creation from the connect api

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'sort_title',
                'forum_topic_id',
                'legacy_guide_url',
                'publisher',
                'developer',
                'genre',
                'image_icon_asset_path',
                'image_box_art_asset_path',
                'image_title_asset_path',
                'image_ingame_asset_path',
                'released_at',
                'released_at_granularity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->useFallbackUrl(asset('assets/images/game/icon.webp'))
            ->singleFile()
            ->registerMediaConversions(function () {
                /**
                 * @see /config/media.php
                 */
                $iconSizes = [
                    /*
                     * used on detail pages
                     */
                    '2xl',

                    /*
                     * used everywhere else
                     */
                    'md',
                ];

                foreach ($iconSizes as $iconSize) {
                    $width = config('media.icon.' . $iconSize . '.width');
                    $height = config('media.icon.' . $iconSize . '.height');
                    $this->addMediaConversion($iconSize)
                        ->nonQueued()
                        ->format('png')
                        ->fit(Fit::Contain, $width, $height)
                        ->fit(Fit::Fill, $width, $height)
                        ->optimize();
                }
            });
    }

    // == search

    public function toSearchableArray(): array
    {
        // Get alternative titles from the game's list of releases.
        $altTitles = $this->releases()
            ->where('is_canonical_game_title', false)
            ->pluck('title')
            ->toArray();

        $this->loadMissing('system');

        // Generate all search titles (main title + alternative titles + system variations).
        $searchTitles = (new ComputeGameSearchTitlesAction())->execute(
            $this->title,
            $this->system->name,
            $this->system->name_short,
            $altTitles
        );

        // Check if game has any tags - we rank these lower.
        // Otherwise stuff like "~Hack~ SM64: Whatever" might actually rank higher than "Super Mario 64".
        $isTagged = $this->tags()
            ->whereType('game')
            ->whereIn('name->en', ['Demo', 'Hack', 'Homebrew', 'Prototype', 'Test Kit', 'Unlicensed'])
            ->exists() ? 1 : 0;

        /**
         * Calculate a naive popularity score based on player count.
         * We actually use a three-tier approach for ranking games in search, relying on
         * `has_players`, `players_total`, and `popularity_score`.
         *
         * `has_players` is binary: 0 or 1.
         * It's a super-quick filter to separate games with ANY players from those with none.
         * This allows Meilisearch to efficiently group all games with players above those
         * without any players at all.
         * EXAMPLE: A game with 1 player ranks higher than a game with 0 players.
         *
         * `players_total` is an exact count: 0, 1, 2, 3... 20000... 40000...)
         * This gives us fine-grained sorting within games that have players.
         * Among games with players, we want those with more players to rank higher.
         * EXAMPLE: A game with 50,000 players ranks higher than one with 100 players.
         *
         * `popularity_score` is tiered: 0, 1, 2, 3, 4, or 5.
         * This creates popularity buckets to group games by popularity ranges in the same
         * search request. This is needed to prevent minor player count differences from
         * dominating search relevance.
         *
         * Without popularity_score, if we only use players_total, a game with 10,001 players
         * will always rank higher than one with 10,000 players, even if the second game is
         * a much better text match for the search query.
         *
         * The intention is for popularity to provide a baseline ranking. It shouldn't
         * completely override text relevance for games of similar popularity.
         */
        $playersTotal = $this->players_total ?? 0;
        $popularityScore = match (true) {
            $playersTotal >= 10000 => 5, // Very popular.
            $playersTotal >= 5000 => 4,  // Popular.
            $playersTotal >= 1000 => 3,  // Moderately popular.
            $playersTotal >= 100 => 2,   // Some players.
            $playersTotal > 0 => 1,      // Few players.
            default => 0,                // No players.
        };

        return [
            'id' => $this->id,
            'title' => $this->title,
            'alt_titles' => $altTitles,
            'search_titles' => $searchTitles,
            'players_total' => $playersTotal,
            'is_subset' => str_contains($this->title, '[Subset') ? 1 : 0,
            'has_players' => $playersTotal > 0 ? 1 : 0,
            'is_tagged' => $isTagged,
            'popularity_score' => $popularityScore,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        if ($this->system_id === System::Hubs || $this->system_id === System::Events) {
            return false;
        }

        return true;
    }

    // == actions

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_icon_asset_path);
    }

    public function getImageBoxArtUrlAttribute(): string
    {
        return media_asset($this->image_box_art_asset_path);
    }

    public function getImageTitleUrlAttribute(): string
    {
        return media_asset($this->image_title_asset_path);
    }

    public function getImageIngameUrlAttribute(): string
    {
        return media_asset($this->image_ingame_asset_path);
    }

    public function getParentGameIdAttribute(): ?int
    {
        return once(function () {
            // Get this game's core achievement set(s).
            $coreAchievementSets = GameAchievementSet::where('game_id', $this->id)
                ->where('type', AchievementSetType::Core)
                ->pluck('achievement_set_id');

            if ($coreAchievementSets->isEmpty()) {
                return null;
            }

            // Check if another game uses any of this game's core achievement sets as a non-core type.
            // This would indicate that the other game is the parent.
            $nonCoreUsage = GameAchievementSet::whereIn('achievement_set_id', $coreAchievementSets)
                ->where('game_id', '!=', $this->id)
                ->where('type', '!=', AchievementSetType::Core)
                ->orderBy('created_at') // if more than one parent exists, take the first associated
                ->select('game_id')
                ->first();

            if ($nonCoreUsage) {
                return $nonCoreUsage->game_id;
            }

            // If no mapping exists, but title includes "[Subset", try to find the parent by name
            $index = strpos($this->title, '[Subset - ');
            if ($index !== false) {
                // Trim to ensure no leading/trailing spaces.
                $baseSetTitle = trim(substr($this->title, 0, $index));

                // Attempt to find a game with the base title and the same system ID.
                return Game::where('title', $baseSetTitle)
                    ->where('system_id', $this->system_id)
                    ->value('id');
            }

            return null;
        });
    }

    public function getIsSubsetGameAttribute(): bool
    {
        return $this->parentGameId !== null;
    }

    public function getCanHaveBeatenTypes(): bool
    {
        $isSubsetOrTestKit = (
            mb_strpos($this->title, "[Subset") !== false
            || mb_strpos($this->title, "~Test Kit~") !== false
        );

        $isEventGame = $this->system_id === System::Events;

        return !$isSubsetOrTestKit && !$isEventGame;
    }

    public function getCanDelegateActivity(User $user): bool
    {
        return $this->getIsStandalone() && $this->getHasAuthoredSomeAchievements($user);
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('game.show', [$this, $this->getSlugAttribute()]);
    }

    public function getHasMatureContentAttribute(): bool
    {
        return $this->gameSets()->where('has_mature_content', true)->exists();
    }

    public function getLastUpdatedAttribute(): Carbon
    {
        return $this->last_achievement_update ?? $this->updated_at;
    }

    public function getPermalinkAttribute(): string
    {
        return route('game.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    public function getHasAuthoredSomeAchievements(User $user): bool
    {
        if ($this->achievements->isEmpty()) {
            return false;
        }

        $this->achievements->loadMissing('developer');

        // Check if any achievement is authored by the given user.
        return $this->achievements->some(function ($achievement) use ($user) {
            return $achievement->Flags === AchievementFlag::OfficialCore->value && $achievement->developer->id === $user->id;
        });
    }

    public function getIdTitleAttribute(): string
    {
        return '[' . $this->id . '] ' . $this->title;
    }

    public function getIsStandalone(): bool
    {
        return $this->system_id === System::Standalones;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<Achievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'GameID');
    }

    /**
     * @return BelongsToMany<AchievementSet, $this>
     */
    public function achievementSets(): BelongsToMany
    {
        return $this->belongsToMany(AchievementSet::class, 'game_achievement_sets', 'game_id', 'achievement_set_id', 'id', 'id')
            ->withPivot(['type', 'title', 'order_column'])
            ->withTimestamps('created_at', 'updated_at');
    }

    /**
     * @return HasMany<AchievementSetClaim, $this>
     */
    public function achievementSetClaims(): HasMany
    {
        return $this->hasMany(AchievementSetClaim::class, 'game_id');
    }

    public function parentGame(): ?Game
    {
        return once(function (): ?Game {
            return $this->parentGameId ? Game::find($this->parentGameId) : null;
        });
    }

    /**
     * @return HasManyThrough<AchievementSetAuthor, GameAchievementSet, $this>
     */
    public function coreSetAuthorshipCredits(): HasManyThrough
    {
        return $this->hasManyThrough(
            AchievementSetAuthor::class,
            GameAchievementSet::class,
            'game_id',
            'achievement_set_id',
            'id',
            'achievement_set_id'
        )->where(DB::raw('game_achievement_sets.type'), AchievementSetType::Core);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::Game);
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
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function claimsComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::SetClaim);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function visibleClaimsComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->claimsComments()->visibleTo($currentUser);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function hashesComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::GameHash);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function visibleHashesComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->hashesComments()->visibleTo($currentUser);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function modificationsComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::GameModification);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function visibleModificationsComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->modificationsComments()->visibleTo($currentUser);
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    /**
     * @return HasOne<Achievement, $this>
     */
    public function lastAchievementUpdate(): HasOne
    {
        return $this->hasOne(Achievement::class, 'GameID')->latest('DateModified');
    }

    /**
     * @return HasMany<Leaderboard, $this>
     */
    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'GameID', 'id');
    }

    /**
     * TODO will need to be modified if game_id is migrated to game_hash_set_id
     *
     * @return HasMany<MemoryNote, $this>
     */
    public function memoryNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class, 'game_id');
    }

    /**
     * @return HasMany<PlayerBadge, $this>
     */
    public function playerBadges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'AwardData', 'id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function playerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_games')
            ->using(PlayerGame::class);
    }

    /**
     * @return HasMany<PlayerGame, $this>
     */
    public function playerGames(): HasMany
    {
        return $this->hasMany(PlayerGame::class, 'game_id');
    }

    /**
     * @return HasMany<PlayerSession, $this>
     */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class, 'game_id');
    }

    /**
     * @return HasMany<GameRelease, $this>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(GameRelease::class, 'game_id', 'id');
    }

    /**
     * @return HasMany<GameAchievementSet, $this>
     */
    public function gameAchievementSets(): HasMany
    {
        return $this->hasMany(GameAchievementSet::class, 'game_id', 'id');
    }

    /**
     * @return HasMany<GameAchievementSet, $this>
     */
    public function selectableGameAchievementSets(): HasMany
    {
        return $this->gameAchievementSets()
            ->orderBy('order_column')
            ->with('achievementSet');
    }

    /**
     * @return BelongsToMany<GameSet, $this>
     */
    public function gameSets(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_games', 'game_id', 'game_set_id')
            ->withPivot(['created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps('created_at', 'updated_at');
    }

    /**
     * @return BelongsToMany<GameSet, $this>
     */
    public function hubs(): BelongsToMany
    {
        return $this->gameSets()->whereType(GameSetType::Hub);
    }

    /**
     * @return BelongsToMany<GameSet, $this>
     */
    public function similarGames(): BelongsToMany
    {
        return $this->gameSets()->whereType(GameSetType::SimilarGames);
    }

    /**
     * @return BelongsToMany<Game, GameSet>
     */
    public function similarGamesList(): BelongsToMany
    {
        // This should always be truthy.
        $gameSet = GameSet::query()
            ->whereGameId($this->id)
            ->whereType(GameSetType::SimilarGames)
            ->first();

        // Return an empty relationship if no game set exists.
        if (!$gameSet) {
            return $this->belongsToMany(Game::class, 'game_set_games')->whereRaw('1 = 0');
        }

        return $gameSet->games()->with('system')->withTimestamps(['created_at', 'updated_at']);
    }

    /**
     * @return HasMany<GameHashSet, $this>
     */
    public function gameHashSets(): HasMany
    {
        return $this->hasMany(GameHashSet::class, 'game_id');
    }

    /**
     * @return HasMany<UserGameListEntry, $this>
     */
    public function gameListEntries(): HasMany
    {
        return $this->hasMany(UserGameListEntry::class, 'GameID', 'id');
    }

    /**
     * @return HasMany<GameHash, $this>
     */
    public function hashes(): HasMany
    {
        return $this->hasMany(GameHash::class, 'game_id');
    }

    /**
     * @return HasMany<GameHash, $this>
     */
    public function compatibleHashes(): HasMany
    {
        return $this->hashes()->where('compatibility', GameHashCompatibility::Compatible);
    }

    /**
     * @return HasMany<Leaderboard, $this>
     */
    public function visibleLeaderboards(): HasMany
    {
        return $this->leaderboards()->visible();
    }

    /**
     * @return HasManyThrough<Ticket, Achievement, $this>
     */
    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, Achievement::class, 'GameID', 'AchievementID', 'id', 'ID');
    }

    /**
     * @return HasManyThrough<Ticket, Achievement, $this>
     */
    public function unresolvedTickets(): HasManyThrough
    {
        return $this->tickets()->unresolved();
    }

    /**
     * @return BelongsTo<Trigger, $this>
     */
    public function currentTrigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'ID');
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

    /**
     * @return HasOne<Event, $this>
     */
    public function event(): HasOne
    {
        return $this->hasOne(Event::class, 'legacy_game_id');
    }

    // == scopes

    /**
     * @param Builder<Game> $query
     * @return Builder<Game>
     */
    public function scopeWhereHasPublishedAchievements($query): Builder
    {
        return $query->where('achievements_published', '>', 0);
    }

    /**
     * @param Builder<Game> $query
     * @return Builder<Game>
     */
    public function scopeWithLastAchievementUpdate(Builder $query): Builder
    {
        return $query->addSelect([
            'last_achievement_update' => Achievement::select('DateModified')
                ->whereColumn('Achievements.GameID', 'games.id')
                ->orderBy('DateModified', 'desc')
                ->limit(1),
        ]);
    }
}
