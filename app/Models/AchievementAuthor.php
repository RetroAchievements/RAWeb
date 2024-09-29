<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AchievementAuthor extends BaseModel
{
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'achievement_authors';

    protected $fillable = [
        'achievement_id',
        'user_id',
        'task',
        'created_at', // Used for backdating.
        'updated_at',
    ];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'achievement_id',
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
     * @return BelongsTo<Achievement, AchievementAuthor>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'ID');
    }

    /**
     * @return BelongsTo<User, AchievementAuthor>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withTrashed();
    }

    // == scopes
}
