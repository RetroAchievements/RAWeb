<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use RA\AwardType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Badge extends BaseModel implements HasMedia
{
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    private static array $developerCountBoundaries = [
        100,
        250,
        500,
        1000,
        2500,
        5000,
        10000,
        25000,
        50000,
        100000,
        250000,
        500000,
        1000000,
        2500000,
        5000000,
    ];

    private static array $developerPointBoundaries = [
        1000,
        2500,
        5000,
        10000,
        25000,
        50000,
        100000,
        250000,
        500000,
        1000000,
        2500000,
        5000000,
        10000000,
        25000000,
        50000000,
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

    // == helpers

    private static function getThresholds(int $awardType): ?array
    {
        switch ($awardType) {
            case AwardType::AchievementUnlocksYield:
                return self::$developerCountBoundaries;

            case AwardType::AchievementPointsYield:
                return self::$developerPointBoundaries;

            default:
                return null;
        }
    }

    public static function getBadgeThreshold(int $awardType, int $tier): int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds === null)
            return 0;

        if ($tier < 0 || $tier >= count($thresholds))
            return 0;

        return $thresholds[$tier];
    }

    public static function getNewBadgeTier(int $awardType, int $oldValue, int $newValue): ?int
    {
        $thresholds = self::getThresholds($awardType);
        if ($thresholds !== null) {
            for ($i = count($thresholds) - 1; $i >= 0; $i--) {
                if ($newValue >= $thresholds[$i] && $oldValue < $thresholds[$i])
                    return $i;
            }
        }

        return null;
    }
}
