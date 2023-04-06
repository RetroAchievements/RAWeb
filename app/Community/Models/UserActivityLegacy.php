<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;

class UserActivityLegacy extends BaseModel
{
    // TODO drop Activity table, migrate to UserActivity/user_activities model
    protected $table = 'Activity';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'timestamp';
    public const UPDATED_AT = 'lastupdate';

    protected $fillable = [
        'User',
        'timestamp',
        'lastupdate',
        'activitytype',
        'data',
        'data2',
    ];
}
