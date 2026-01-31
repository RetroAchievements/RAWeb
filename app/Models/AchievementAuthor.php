<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Actions\LogAchievementCreditActivityAction;
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

    protected static function booted(): void
    {
        $logAction = new LogAchievementCreditActivityAction();

        static::created(fn (AchievementAuthor $credit) => $logAction->execute('create', $credit));

        static::updated(fn (AchievementAuthor $credit) => $logAction->execute(
            'update',
            $credit,
            $credit->getOriginal(),
            $credit->getChanges()
        ));

        static::deleted(fn (AchievementAuthor $credit) => $logAction->execute('delete', $credit));
    }

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
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    // == scopes
}
