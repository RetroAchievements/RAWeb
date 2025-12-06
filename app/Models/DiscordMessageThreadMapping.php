<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\ModerationReportableType;
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

    /**
     * Find an existing Discord thread mapping for a reportable item.
     * If multiple users have reported the same item, this finds a mapping
     * from any of those reports.
     */
    public static function findReportMapping(ModerationReportableType $reportableType, int $reportableId): ?self
    {
        $reportThreadIds = UserModerationReport::where('reportable_type', $reportableType->value)
            ->where('reportable_id', $reportableId)
            ->pluck('message_thread_id');

        if ($reportThreadIds->isEmpty()) {
            return null;
        }

        return self::whereIn('message_thread_id', $reportThreadIds)->first();
    }
}
