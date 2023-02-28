<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class UserActivity extends BaseModel
{
    protected $table = 'Activity';

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
