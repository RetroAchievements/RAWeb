<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordMessageThreadMapping extends BaseModel
{
    protected $table = 'discord_message_thread_mappings';

    protected $fillable = [
        'message_thread_id',
        'recipient_id',
        'discord_thread_id',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<MessageThread, DiscordMessageThreadMapping>
     */
    public function messageThread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    /**
     * @return BelongsTo<User, DiscordMessageThreadMapping>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id', 'ID');
    }

    // == scopes

    /**
     * Find an existing Discord thread mapping for a message thread and recipient.
     */
    public static function findMapping(int $messageThreadId, int $recipientId): ?self
    {
        return self::where('message_thread_id', $messageThreadId)
            ->where('recipient_id', $recipientId)
            ->first();
    }

    // == helpers

    /**
     * Store a new Discord thread mapping.
     */
    public static function storeMapping(int $messageThreadId, int $recipientId, string $discordThreadId): self
    {
        return self::updateOrCreate(
            [
                'message_thread_id' => $messageThreadId,
                'recipient_id' => $recipientId,
            ],
            [
                'discord_thread_id' => $discordThreadId,
            ]
        );
    }
}
