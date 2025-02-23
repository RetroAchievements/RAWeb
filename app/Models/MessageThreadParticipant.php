<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\MessageThreadParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThreadParticipant extends BaseModel
{
    /** @use HasFactory<MessageThreadParticipantFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'thread_id',
        'num_unread',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): MessageThreadParticipantFactory
    {
        return MessageThreadParticipantFactory::new();
    }

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
