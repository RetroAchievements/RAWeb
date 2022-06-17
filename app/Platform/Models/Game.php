<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Community\Concerns\HasGameCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\Concerns\PreventLazyLoading;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
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
    use LogsActivity;
    use PreventLazyLoading;
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'system_id',
        'release',
        'title',
    ];

    protected $with = [
        'system',
        'media',
    ];

    /**
     * @see \App\Support\Database\Eloquent\Concerns\PreventLazyLoading
     */
    protected array $allowedLazyRelations = [
        /*
         * has to be lazy loadable for singleFile() collections
         */
        'media',
    ];

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
            'id',
            'title',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
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
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_games')
            // ->using(BasePivot::class)
            ->using(PlayerGame::class);
    }

    public function gameHashSets(): HasMany
    {
        return $this->hasMany(GameHashSet::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    // == scopes
}
