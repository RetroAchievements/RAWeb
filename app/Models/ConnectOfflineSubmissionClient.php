<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConnectOfflineSubmissionClient extends BaseModel
{
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'connect_offline_submission_clients';

    protected $fillable = [
        'client',
    ];

    // audit activity log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['client'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
