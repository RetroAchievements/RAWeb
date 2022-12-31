<?php

namespace LegacyApp\Support\Database\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

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
