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
        'moderation_report_id',
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

    /**
     * @return BelongsTo<UserModerationReport, $this>
     */
    public function moderationReport(): BelongsTo
    {
        return $this->belongsTo(UserModerationReport::class, 'moderation_report_id');
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
    public static function findReportMapping(ModerationReportableType $reportableType, int $reportableId): ?self
    {
        $report = UserModerationReport::where('reportable_type', $reportableType->value)
            ->where('reportable_id', $reportableId)
            ->whereHas('discordThreadMapping')
            ->first();

        return $report?->discordThreadMapping;
    }

    /**
     * Store a new Discord thread mapping for a moderation report.
     */
    public static function storeReportMapping(
        int $moderationReportId,
        string $discordThreadId,
        int $messageThreadId,
    ): self {
        return self::updateOrCreate(
            [
                'message_thread_id' => $messageThreadId,
            ],
            [
                'discord_thread_id' => $discordThreadId,
                'moderation_report_id' => $moderationReportId,
            ]
        );
    }
}
