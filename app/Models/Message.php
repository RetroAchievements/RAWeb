<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends BaseModel
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $table = 'messages';

    public const UPDATED_AT = null;

    protected $fillable = [
        'thread_id',
        'author_id',
        'sent_by_id',
        'body',
        'created_at',
    ];

    protected $casts = [
        'Unread' => 'boolean',
    ];

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Message>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<User, Message>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id', 'ID')->withTrashed();
    }

    // == scopes
}
