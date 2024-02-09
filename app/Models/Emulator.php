<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Emulator extends BaseModel implements HasMedia
{
    use SoftDeletes;
    use SortableTrait;
    use InteractsWithMedia;

    protected $fillable = [
        'active',
        'name',
        'description',
        'link',
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

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<System>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'system_emulators')
            ->using(BasePivot::class)
            ->withTimestamps();
    }

    /**
     * @return HasOne<EmulatorRelease>
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(EmulatorRelease::class)
            ->where('stable', true)
            ->orderBy('version', 'DESC');
    }

    /**
     * @return HasOne<EmulatorRelease>
     */
    public function latestBetaRelease(): HasOne
    {
        return $this->hasOne(EmulatorRelease::class)
            ->where('stable', false)
            ->orderBy('version', 'DESC');
    }

    /**
     * @return HasMany<EmulatorRelease>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(EmulatorRelease::class);
    }

    // == scopes

    /**
     * @param Builder<Emulator> $query
     * @return Builder<Emulator>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
