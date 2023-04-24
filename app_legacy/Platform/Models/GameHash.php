<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class GameHash extends BaseModel
{
    protected $table = 'GameHashLibrary';

    protected $fillable = [
        'GameID',
        'MD5',
        'User',
        'Name',
        'Label',
    ];

    public const UPDATED_AT = null;
}
