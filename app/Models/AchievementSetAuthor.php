<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\AchievementSetAuthorTask;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AchievementSetAuthor extends BaseModel
{
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'achievement_set_authors';

    protected $fillable = [
        'achievement_set_id',
        'user_id',
        'task',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'task' => AchievementSetAuthorTask::class,
    ];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'achievement_set_id',
                'user_id',
                'task',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<AchievementSet, AchievementSetAuthor>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class);
    }

    /**
     * @return BelongsTo<User, AchievementSetAuthor>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withTrashed();
    }

    // == scopes
}
