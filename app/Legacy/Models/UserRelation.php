<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

class UserRelation extends BaseModel
{
    protected $table = 'Friends';
}
