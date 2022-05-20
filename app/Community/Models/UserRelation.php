<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;

class UserRelation extends BaseModel
{
    // TODO rename Friends table to user_relations
    // TODO migrate Friendship column to status
    protected $table = 'Friends';

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
