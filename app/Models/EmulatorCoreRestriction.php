<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientSupportLevel;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmulatorCoreRestriction extends BaseModel
{
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'emulator_core_restrictions';

    protected $fillable = [
        'core_name',
        'support_level',
        'minimum_version',
        'recommendation',
        'notes',
    ];

    protected $casts = [
        'support_level' => ClientSupportLevel::class,
    ];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'core_name',
                'support_level',
                'minimum_version',
                'recommendation',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    // == relations

    // == scopes

    /**
     * @param Builder<EmulatorCoreRestriction> $query
     * @return Builder<EmulatorCoreRestriction>
     */
    public function scopeForCore(Builder $query, string $coreName): Builder
    {
        // Normalize dots to underscores so iOS-packaged cores (ie: "dolphin.libretro")
        // match the underscore form stored in the database.
        $normalized = strtolower(str_replace('.', '_', $coreName));

        return $query->whereRaw("LOWER(REPLACE(core_name, '.', '_')) = ?", [$normalized]);
    }
}
