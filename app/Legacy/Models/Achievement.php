<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

/**
 * @property string $BadgeName
 * @property string $Description
 * @property string $Title
 */
class Achievement extends BaseModel
{
    protected $table = 'Achievements';

    public const CREATED_AT = 'DateCreated';

    protected $dates = [
        'DateModified',
    ];
}
