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
        'discord_thread_id',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function messageThread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    // == scopes

    /**
     * Find an existing Discord thread mapping for a message thread.
     */
    public static function findMapping(int $messageThreadId): ?self
    {
        return self::where('message_thread_id', $messageThreadId)
            ->first();
    }

    // == helpers

    /**
     * Store a new Discord thread mapping.
     */
    public static function storeMapping(int $messageThreadId, string $discordThreadId): self
    {
        return self::updateOrCreate(
            [
                'message_thread_id' => $messageThreadId,
            ],
            [
                'discord_thread_id' => $discordThreadId,
            ]
        );
    }
}
