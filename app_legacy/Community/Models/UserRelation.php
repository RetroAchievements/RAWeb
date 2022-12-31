<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class UserRelation extends BaseModel
{
    protected $table = 'Friends';
}
