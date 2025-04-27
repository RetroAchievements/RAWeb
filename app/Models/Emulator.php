<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\EmulatorFactory;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Emulator extends BaseModel implements HasMedia
{
    /*
     * Framework Traits
     */
    use SoftDeletes;
    use SortableTrait;
    /** @use HasFactory<EmulatorFactory> */
    use HasFactory;

    /*
     * Providers Traits
     */
    use PivotEventTrait;
    use InteractsWithMedia;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $fillable = [
        'name',
        'original_name',
        'description',
        'active',
        'can_debug_triggers',
        'documentation_url',
        'download_url',
        'download_x64_url',
        'source_url',
        'website_url',
    ];

    protected $casts = [
        'active' => 'boolean',
        'can_debug_triggers' => 'boolean',
    ];

    public const NonEmulator = 22;

    protected static function newFactory(): EmulatorFactory
    {
        return EmulatorFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'systems') {
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
            if ($relationName === 'systems') {
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

    // audit activity log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'original_name',
                'description',
                'active',
                'documentation_url',
                'download_url',
                'source_url',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('2xl')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 500, 500);
                $this->addMediaConversion('32')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 64, 64);
                $this->addMediaConversion('64')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 64, 64);
            });
    }

    // == accessors

    public function getHasOfficialSupportAttribute(): bool
    {
        // Officially supported emulators must be set to active and 
        // have at least one user agent registered in the DB.
        if (!$this->active || $this->userAgents->isEmpty()) {
            return false;
        }

        foreach ($this->userAgents as $userAgent) {
            // Case 1: Has minimum_hardcore_version. This implies there's at least one
            // version of the emulator out there which we fully support.
            if ($userAgent->minimum_hardcore_version || $userAgent->minimum_allowed_version) {
                return true;
            }

            // Case 2: Emulator is active and doesn't have minimum versions.
            if ($this->active) {
                return true;
            }
        }

        return false;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<Platform>
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'emulator_platforms')
            ->using(BasePivot::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<System>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'system_emulators', 'emulator_id', 'system_id')
            ->using(BasePivot::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<EmulatorDownload>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(EmulatorDownload::class);
    }

    /**
     * @return HasOne<EmulatorRelease>
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(EmulatorRelease::class)
            ->where('stable', true)
            ->orderBy('created_at', 'DESC');
    }

    /**
     * @return HasOne<EmulatorRelease>
     */
    public function minimumSupportedRelease(): HasOne
    {
        return $this->hasOne(EmulatorRelease::class)
            ->where('minimum', true)
            ->orderBy('created_at', 'DESC');
    }

    /**
     * @return HasOne<EmulatorRelease>
     */
    public function latestBetaRelease(): HasOne
    {
        return $this->hasOne(EmulatorRelease::class)
            ->where('stable', false)
            ->orderBy('created_at', 'DESC');
    }

    /**
     * @return HasMany<EmulatorRelease>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(EmulatorRelease::class);
    }

    /**
     * @return HasMany<EmulatorUserAgent>
     */
    public function userAgents(): HasMany
    {
        return $this->hasMany(EmulatorUserAgent::class);
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

    /**
     * @param Builder<Emulator> $query
     * @return Builder<Emulator>
     */
    public function scopeForSystem(Builder $query, int $systemId): Builder
    {
        return $query->whereHas('systems', function ($query) use ($systemId) {
            $query->where('system_id', $systemId);
        });
    }
}
