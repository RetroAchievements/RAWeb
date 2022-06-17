<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Badge extends BaseModel implements HasMedia
{
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    public static array $developerCountBoundaries = [
        5,
        10,
        50,
        100,
        200,
        400,
        600,
        800,
        1000,
        2000,
        3000,
        4000,
        5000,
        6000,
    ];

    public static array $developerPointBoundaries = [
        100,
        200,
        300,
        500,
        800,
        1000,
        1500,
        2000,
        3000,
        4000,
        5000,
        10000,
        15000,
        20000,
        30000,
        40000,
        50000,
        60000,
        70000,
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == media

    public function registerMediaCollections(): void
    {
    }

    // == accessors

    /**
     * TODO: achievements have no media attached! badges do
     * achievements refer to a particular stage of a badge
     */
    public function getBadgeAttribute(): string
    {
        return $this->getBadgeUnlockedAttribute();
    }

    public function getBadgeLockedAttribute(): string
    {
        /*
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->BadgeName . '_lock.png';
        // if (!file_exists(public_path($badge))) {
        //     $badge = 'assets/images/badge/badge-locked.png';
        // }
        // return $badge;
        return '';
    }

    public function getBadgeUnlockedAttribute(): string
    {
        /*
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->BadgeName . '.png';
        // if (!file_exists(public_path($badge))) {
        //     $badge = 'assets/images/badge/badge.png';
        // }
        // return $badge;
        return '';
    }

    // public function getTitleAttribute(): string
    // {
    //     return !empty(trim($this->attributes['Title'])) ? $this->attributes['Title'] : 'Untitled';
    // }

    // == mutators

    // == relations

    public function badgeable(): MorphTo
    {
        return $this->morphTo();
    }

    // == scopes
}
