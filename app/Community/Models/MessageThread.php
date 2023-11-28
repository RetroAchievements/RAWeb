<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;

class MessageThread extends BaseModel
{
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
