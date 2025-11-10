<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserModerationReport extends BaseModel
{
    protected $table = 'user_moderation_reports';

    protected $fillable = [
        'reporter_user_id',
        'reported_user_id',
        'reportable_type',
        'reportable_id',
        'message_thread_id',
        'created_at',
        'updated_at',
    ];

    // == relations

    /**
     * @return MorphTo<BaseModel, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function messageThread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    /**
     * @return HasOne<DiscordMessageThreadMapping, $this>
     */
    public function discordThreadMapping(): HasOne
    {
        return $this->hasOne(DiscordMessageThreadMapping::class, 'moderation_report_id');
    }
}
