<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\DiscordReportableType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordMessageThreadMapping extends BaseModel
{
    protected $table = 'discord_message_thread_mappings';

    protected $fillable = [
        'message_thread_id',
        'discord_thread_id',
        'reportable_type',
        'reportable_id',
    ];

    protected $casts = [
        'reportable_type' => DiscordReportableType::class,
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

    /**
     * Find an existing Discord thread mapping for a reportable item.
     */
    public static function findReportMapping(DiscordReportableType $reportableType, int $reportableId): ?self
    {
        return self::where('reportable_type', $reportableType->value)
            ->where('reportable_id', $reportableId)
            ->first();
    }

    /**
     * Store a new Discord thread mapping for a reportable item.
     */
    public static function storeReportMapping(
        DiscordReportableType $reportableType,
        int $reportableId,
        string $discordThreadId,
        int $messageThreadId,
    ): self {
        return self::updateOrCreate(
            [
                'reportable_type' => $reportableType->value,
                'reportable_id' => $reportableId,
            ],
            [
                'discord_thread_id' => $discordThreadId,
                'message_thread_id' => $messageThreadId,
            ]
        );
    }
}
