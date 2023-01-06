<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class Achievement extends BaseModel
{
    protected $table = 'Achievements';

    public const CREATED_AT = 'DateCreated';

    protected $dates = [
        'DateModified',
    ];
}
