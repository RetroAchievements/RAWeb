<?php

declare(strict_types=1);

namespace App\Support\Database\Models;

use App\Support\Database\Eloquent\BaseModel;

class DeletedModels extends BaseModel
{
    protected $table = 'DeletedModels';

    public const CREATED_AT = 'Deleted';
    public const UPDATED_AT = null;

    protected $fillable = [
        'ModelType',
        'ModelID',
        'DeletedByUserID',
    ];
}
