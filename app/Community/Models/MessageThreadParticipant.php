<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThreadParticipant extends BaseModel
{
    use SoftDeletes;

    protected $table = 'message_thread_participants';

    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'thread_id',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
