<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class System extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;
    use DiscussedInForum;

    protected $fillable = [
        'name',
        'name_full',
        'name_short',
        'manufacturer',
        'order_column',
        'active',
    ];

    protected $with = [
        'media',
    ];

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('2xl')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 500, 500);
                $this->addMediaConversion('32')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 64, 64);
                $this->addMediaConversion('64')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 64, 64);
            });
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'name',
            'name_full',
            'name_short',
            'manufacturer',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        if (!$this->active) {
            return false;
        }

        return true;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('system.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('system.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return ($this->name_full ?? $this->name) ? '-' . Str::slug($this->name_full ?? $this->name) : '';
    }

    public function getAchievementsLinkAttribute(): string
    {
        return route('system.achievements', [$this->id, $this->getSlugAttribute()]);
    }

    public function getGamesLinkAttribute(): string
    {
        return route('system.game.index', [$this->id, $this->getSlugAttribute()]);
    }

    // == mutators

    // == relations

    public function emulators(): BelongsToMany
    {
        return $this->belongsToMany(Emulator::class, 'system_emulators')
            ->using(BasePivot::class)
            ->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * TODO: store achievements_published and achievements_total on games to be easily filterable
     */
    public function achievementGames(): HasMany
    {
        return $this->hasMany(Game::class)->where('achievements_published', '>', 0);
    }

    public function achievements(): HasManyThrough
    {
        return $this->hasManyThrough(Achievement::class, Game::class);
    }

    // == scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeHasAchievements(Builder $query): Builder
    {
        $query->withCount('achievements');

        return $query->having('achievements_count', '>', '0');
    }

    public function scopeHasGames(Builder $query): Builder
    {
        $query->withCount('games');

        return $query->having('games_count', '>', '0');
    }
}
