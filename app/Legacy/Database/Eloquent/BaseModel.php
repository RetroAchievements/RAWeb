<?php

declare(strict_types=1);

namespace App\Legacy\Database\Eloquent;

use App\Support\Database\Eloquent\BaseModel as AppBaseModel;

class BaseModel extends AppBaseModel
{
    protected $connection = 'mysql_legacy';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';
}
