<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThreadParticipant extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'thread_id',
        'created_at',
        'updated_at',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
