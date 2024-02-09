<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Model;
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
        // TODO return true;
        return false;
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

    /**
     * @return MorphTo<Model, Badge>
     */
    public function badgeable(): MorphTo
    {
        return $this->morphTo();
    }

    // == scopes

}
