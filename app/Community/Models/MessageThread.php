<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;

class MessageThread extends BaseModel
{
    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
