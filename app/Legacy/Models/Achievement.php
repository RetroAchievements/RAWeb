<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

class Achievement extends BaseModel
{
    protected $table = 'Achievements';

    public const CREATED_AT = 'DateCreated';

    protected $dates = [
        'DateModified',
    ];
}
