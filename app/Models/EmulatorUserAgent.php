<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmulatorUserAgent extends BaseModel
{
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'emulator_user_agents';

    protected $fillable = [
        'emulator_id',
        'client',
        'minimum_allowed_version',
        'minimum_hardcore_version',
    ];

    // audit activity log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'client',
                'minimum_allowed_version',
                'minimum_hardcore_version',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Emulator, EmulatorRelease>
     */
    public function emulator(): BelongsTo
    {
        return $this->belongsTo(Emulator::class);
    }

    // == scopes
}
