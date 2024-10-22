<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;

class EventAchievement extends BaseModel
{
    // TODO use LogsActivity;

    protected $table = 'event_achievements';

    protected $fillable = [
        'achievement_id',
        'source_achievement_id',
        'active_from',
        'active_until',
    ];

    protected $casts = [
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Achievement, EventAchievement>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'ID');
    }

    /**
     * @return BelongsTo<Achievement, EventAchievement>
     */
    public function sourceAchievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'source_achievement_id', 'ID');
    }

    // == scopes

    /**
     * @param Builder<EventAchievement> $query
     * @return Builder<EventAchievement>
     */
    public function scopeActive(Builder $query, ?Carbon $timestamp = null): Builder
    {
        $timestamp ??= Carbon::now();

        return $query->where(function ($q) use ($timestamp) {
                $q->where('active_from', '<=', $timestamp)->orWhereNull('active_from');
            })
            ->where(function ($q) use ($timestamp) {
                $q->where('active_until', '>', $timestamp)->orWhereNull('active_until');
            });
    }
}
