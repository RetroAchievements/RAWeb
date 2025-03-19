<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\PlatformExecutionEnvironment;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends BaseModel
{
    protected $fillable = [
        'name',
        'execution_environment',
        'order_column',
    ];

    protected $casts = [
        'execution_environment' => PlatformExecutionEnvironment::class,
    ];

    // == accessors

    /**
     * @return BelongsToMany<Emulator>
     */
    public function emulators(): BelongsToMany
    {
        return $this->belongsToMany(Emulator::class, 'emulator_platforms')
            ->using(BasePivot::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<EmulatorDownload>
     */
    public function emulatorDownloads(): HasMany
    {
        return $this->hasMany(EmulatorDownload::class);
    }

    // == mutators

    // == relations

    // == scopes
}
