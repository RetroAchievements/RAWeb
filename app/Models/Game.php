<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Community\Concerns\HasGameCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Platform\Enums\AchievementFlag;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Image\Manipulations;
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
    // TODO use LogsActivity;
    use HasFactory;
    use InteractsWithMedia;

    use Searchable;
    use SoftDeletes;

    // TODO rename GameData table to games
    // TODO rename ID column to id
    // TODO rename Title column to title
    // TODO rename ConsoleID column to system_id
    // TODO rename Publisher column to publisher
    // TODO rename Developer column to developer
    // TODO rename Genre column to genre
    // TODO rename Released to release
    // TODO rename TotalTruePoints to points_weighted
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
    ];

    protected $visible = [
        'ID',
        'Title',
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

    // protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty();
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
                        ->fit(Manipulations::FIT_CONTAIN, $width, $height)->apply()
                        ->fit(Manipulations::FIT_FILL, $width, $height)
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

    // == temp subset detection

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

    public function getCanHaveBeatenTypes(): bool
    {
        $isSubsetOrTestKit = (
            mb_strpos($this->Title, "[Subset") !== false
            || mb_strpos($this->Title, "~Test Kit~") !== false
        );

        $isEventGame = $this->ConsoleID === 101;

        return !$isSubsetOrTestKit && !$isEventGame;
    }

    public function getCanDelegateActivity(User|string $user): bool
    {
        return $this->getIsStandalone() && $this->getHasAuthoredSomeAchievements($user);
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('game.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('game.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->Title ? '-' . Str::slug($this->Title) : '';
    }

    public function getHasAuthoredSomeAchievements(User|string $user): bool
    {
        if ($this->achievements->isEmpty()) {
            return false;
        }

        $username = $user instanceof User ? $user->User : $user;

        // Check if any achievement is authored by the given user.
        return $this->achievements->some(function ($achievement) use ($username) {
            return $achievement->Flags === AchievementFlag::OfficialCore && $achievement->Author === $username;
        });
    }

    public function getIdTitleAttribute(): string
    {
        return '[' . $this->id . '] ' . $this->title;
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
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
     * @return BelongsTo<System, Game>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'ConsoleID');
    }

    /**
     * @return BelongsTo<System, Game>
     */
    public function console(): BelongsTo
    {
        return $this->system();
    }

    /**
     * @return HasMany<Leaderboard>
     */
    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'GameID');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function players(): BelongsToMany
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
     * @return HasMany<GameHashSet>
     */
    public function gameHashSets(): HasMany
    {
        return $this->hasMany(GameHashSet::class, 'game_id');
    }

    /**
     * @return HasMany<GameHash>
     */
    public function hashes(): HasMany
    {
        return $this->hasMany(GameHash::class, 'game_id');
    }

    // == scopes
}
