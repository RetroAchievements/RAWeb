<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GameHash extends BaseModel
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'hash',
        'md5',
        'system_id',
        'name',
        'description',
    ];

    protected $casts = [
        'file_names' => 'json',
        'regions' => 'json',
    ];

    public function getRouteKeyName(): string
    {
        return 'hash';
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty();
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'hash',
            'name',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == actions

    public function addFileName(string $gameHashName): self
    {
        if (empty($gameHashName)) {
            return $this;
        }
        $fileNames = $this->file_names;
        if (empty($fileNames)) {
            $fileNames = Arr::wrap($gameHashName);
        } else {
            if (!in_array($gameHashName, $fileNames)) {
                $fileNames[] = $gameHashName;
            }
        }
        $this->file_names = $fileNames;

        return $this;
    }

    public function addRegion(string $region): self
    {
        if (empty($region)) {
            return $this;
        }
        $regions = $this->regions;
        $region = mb_strtolower($region);
        if (empty($regions)) {
            $regions = Arr::wrap($region);
        } else {
            if (!in_array($region, $regions)) {
                $regions[] = $region;
            }
        }
        $this->regions = $regions;

        return $this;
    }

    // == accessors

    // == mutators

    // == relations

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function gameHashSets(): BelongsToMany
    {
        return $this->belongsToMany(GameHashSet::class, 'game_hash_set_hashes')
            ->using(GameHashSetHash::class)
            ->withTimestamps();
    }

    // == scopes
}
