<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientSupportLevel;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmulatorCorePolicy extends BaseModel
{
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'emulator_core_policies';

    protected $fillable = [
        'emulator_id',
        'core_name',
        'support_level',
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
                'recommendation',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Emulator, $this>
     */
    public function emulator(): BelongsTo
    {
        return $this->belongsTo(Emulator::class);
    }

    // == scopes

    /**
     * Finds a core policy using prefix matching on the core name.
     * A stored core_name of "doublecherry" will match an incoming
     * core name of "doublecherrygb".
     *
     * @param Builder<EmulatorCorePolicy> $query
     * @return Builder<EmulatorCorePolicy>
     */
    public function scopeForCore(Builder $query, int $emulatorId, string $coreName): Builder
    {
        $normalizedCoreName = strtolower($coreName);

        return $query->where('emulator_id', $emulatorId)
            ->whereRaw('SUBSTR(?, 1, LENGTH(core_name)) = LOWER(core_name)', [$normalizedCoreName])
            ->orderByRaw('LENGTH(core_name) DESC');
    }
}
