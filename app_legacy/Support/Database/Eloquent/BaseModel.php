<?php

declare(strict_types=1);

namespace LegacyApp\Support\Database\Eloquent;

use App\Support\Database\Eloquent\BaseModel as AppBaseModel;

abstract class BaseModel extends AppBaseModel
{
    protected $connection = 'mysql_legacy';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';
}
