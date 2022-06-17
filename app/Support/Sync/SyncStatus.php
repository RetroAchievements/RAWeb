<?php

declare(strict_types=1);

namespace App\Support\Sync;

use App\Support\Database\Eloquent\BaseModel;

class SyncStatus extends BaseModel
{
    public const CREATED_AT = null;

    public const UPDATED_AT = 'updated';

    protected $table = 'sync_status';

    protected $primaryKey = 'kind';

    public $incrementing = false;

    protected $fillable = [
        'kind',
        'cursor',
        'remaining',
        'updated',
    ];
}
