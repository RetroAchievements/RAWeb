<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Community\Concerns\HasGameCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Community\Models\Rating;
use App\Site\Models\User;
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
        'system_id',
        'release',
        'Title',
    ];

    protected $visible = [
        'ID',
        'Title',
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

    // == actions

    // == accessors

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
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_games')
            // ->using(BasePivot::class)
            ->using(PlayerGame::class);
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
        return $this->hasMany(GameHash::class, 'GameID');
    }

    /**
     * @return HasMany<Rating>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'RatingID');
    }

    // == scopes
}
