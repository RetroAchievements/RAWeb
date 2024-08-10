<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * @return BelongsTo<User, MessageThreadParticipant>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsTo<MessageThread, MessageThreadParticipant>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    // == scopes
}
