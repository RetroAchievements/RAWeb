<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Community\Concerns\HasGameCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\ReleasedAtGranularity;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Game extends BaseModel implements HasComments, HasMedia
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
    use InteractsWithMedia;

    use Searchable;
    use SoftDeletes;

    // TODO rename GameData table to games
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename Title column to title
    // TODO rename ConsoleID column to system_id
    // TODO rename Publisher column to publisher
    // TODO rename Developer column to developer
    // TODO rename Genre column to genre
    // TODO rename Released to release
    // TODO rename TotalTruePoints to points_weighted, remove getPointsWeightedAttribute()
    // TODO drop achievement_set_version_hash, migrate to achievement_sets
    // TODO drop ForumTopicID, migrate to forumable morph
    // TODO drop Flags
    // TODO drop ImageIcon, ImageTitle, ImageInGame, ImageBoxArt, migrate to media
    // TODO drop GuideURL, migrate to forumable morph
    // TODO drop RichPresencePatch, migrate to triggerable morph
    // TODO drop IsFinal
    protected $table = 'GameData';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'release',
        'Title',
        'sort_title',
        'ForumTopicID',
        'Publisher',
        'Developer',
        'Genre',
        'released_at',
        'released_at_granularity',
        'GuideURL',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'released_at_granularity' => ReleasedAtGranularity::class,
        'last_achievement_update' => 'datetime',
    ];

    protected $visible = [
        'ID',
        'Title',
        'sort_title',
        'ConsoleID',
        'ForumTopicID',
        'Flags',
        'ImageIcon',
        'ImageTitle',
        'ImageIngame',
        'ImageBoxArt',
        'Publisher',
        'Developer',
        'Genre',
        'Released',
        'released_at',
        'released_at_granularity',
        'IsFinal',
        'RichPresencePatch',
        'GuideURL',
        'Updated',
        'achievement_set_version_hash',
        'achievements_published',
        'points_total',
        'players_total',
    ];

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    // == logging

    // TODO log game creation from the connect api

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'Title',
                'sort_title',
                'ForumTopicID',
                'GuideURL',
                'Publisher',
                'Developer',
                'Genre',
                'ImageIcon',
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
        return $this->only([
            'ID',
            'Title',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return $this->isPublished();
        // TODO return true;
        return false;
    }

    // TODO refactor when game_achievement_sets is ready
    public function getParentGame(): ?Game
    {
        // Use regular expression to check if the title includes a subset pattern and extract the base title.
        if (preg_match('/(.+)\[Subset - .+\]/', $this->Title, $matches)) {
            // Trim to ensure no leading/trailing spaces.
            $baseSetTitle = trim($matches[1]);

            // Attempt to find a game with the base title and the same console ID.
            $game = Game::where('Title', $baseSetTitle)
                ->where('ConsoleID', $this->ConsoleID)
                ->first();

            // If a matching game is found, return its model.
            return $game ?? null;
        }

        // Return null if the title does not match the subset pattern or no game is found.
        return null;
    }

    // == actions

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->ImageIcon);
    }

    public function getCanHaveBeatenTypes(): bool
    {
        $isSubsetOrTestKit = (
            mb_strpos($this->Title, "[Subset") !== false
            || mb_strpos($this->Title, "~Test Kit~") !== false
        );

        $isEventGame = $this->ConsoleID === 101;

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

    public function getLastUpdatedAttribute(): Carbon
    {
        return $this->last_achievement_update ?? $this->Updated;
    }

    public function getPermalinkAttribute(): string
    {
        return route('game.show', $this);
    }

    public function getPointsWeightedAttribute(): int
    {
        return $this->TotalTruePoints ?? 0;
    }

    public function getSlugAttribute(): string
    {
        return $this->Title ? '-' . Str::slug($this->Title) : '';
    }

    public function getHasAuthoredSomeAchievements(User $user): bool
    {
        if ($this->achievements->isEmpty()) {
            return false;
        }

        $this->achievements->loadMissing('developer');

        // Check if any achievement is authored by the given user.
        return $this->achievements->some(function ($achievement) use ($user) {
            return $achievement->Flags === AchievementFlag::OfficialCore && $achievement->developer->id === $user->id;
        });
    }

    public function getIdTitleAttribute(): string
    {
        return '[' . $this->id . '] ' . $this->title;
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'] ?? 1;
    }

    public function getIsStandalone(): bool
    {
        return $this->ConsoleID === 102;
    }

    public function getSystemIdAttribute(): int
    {
        return $this->attributes['ConsoleID'];
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['Title'] ?? null;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<Achievement>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'GameID');
    }

    /**
     * @return BelongsToMany<AchievementSet>
     */
    public function achievementSets(): BelongsToMany
    {
        return $this->belongsToMany(AchievementSet::class, 'game_achievement_sets', 'game_id', 'achievement_set_id', 'ID', 'id');
    }

    /**
     * @return HasMany<AchievementSetClaim>
     */
    public function achievementSetClaims(): HasMany
    {
        return $this->hasMany(AchievementSetClaim::class, 'game_id');
    }

    /**
     * @return BelongsTo<System, Game>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'ConsoleID');
    }

    /**
     * @return BelongsTo<System, Game>
     *
     * @deprecated use `->system`
     */
    public function console(): BelongsTo
    {
        return $this->system();
    }

    /**
     * @return HasOne<Achievement>
     */
    public function lastAchievementUpdate(): HasOne
    {
        return $this->hasOne(Achievement::class, 'GameID')->latest('DateModified');
    }

    /**
     * @return HasMany<Leaderboard>
     */
    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'GameID', 'ID');
    }

    /**
     * TODO will need to be modified if game_id is migrated to game_hash_set_id
     *
     * @return HasMany<MemoryNote>
     */
    public function memoryNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class, 'game_id');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function playerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_games')
            ->using(PlayerGame::class);
    }

    /**
     * @return HasMany<PlayerGame>
     */
    public function playerGames(): HasMany
    {
        return $this->hasMany(PlayerGame::class, 'game_id');
    }

    /**
     * @return HasMany<PlayerSession>
     */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class, 'game_id');
    }

    /**
     * @return HasMany<GameAchievementSet>
     */
    public function gameAchievementSets(): HasMany
    {
        return $this->hasMany(GameAchievementSet::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function gameSets(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_games', 'game_id', 'game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at', 'deleted_at');
    }

    /**
     * @return HasMany<GameHashSet>
     */
    public function gameHashSets(): HasMany
    {
        return $this->hasMany(GameHashSet::class, 'game_id');
    }

    /**
     * @return HasMany<UserGameListEntry>
     */
    public function gameListEntries(): HasMany
    {
        return $this->hasMany(UserGameListEntry::class, 'GameID', 'ID');
    }

    /**
     * @return HasMany<GameHash>
     */
    public function hashes(): HasMany
    {
        return $this->hasMany(GameHash::class, 'game_id');
    }

    /**
     * @return HasMany<Leaderboard>
     */
    public function visibleLeaderboards(): HasMany
    {
        return $this->leaderboards()->visible();
    }

    /**
     * @return HasManyThrough<Ticket>
     */
    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, Achievement::class, 'GameID', 'AchievementID', 'ID', 'ID');
    }

    /**
     * @return HasManyThrough<Ticket>
     */
    public function unresolvedTickets(): HasManyThrough
    {
        return $this->tickets()->unresolved();
    }

    // == scopes

    /**
     * @param Builder<Game> $query
     * @return Builder<Game>
     */
    public function scopeWithLastAchievementUpdate(Builder $query): Builder
    {
        return $query->addSelect([
            'last_achievement_update' => Achievement::select('DateModified')
                ->whereColumn('Achievements.GameID', 'GameData.ID')
                ->orderBy('DateModified', 'desc')
                ->limit(1),
        ]);
    }
}
