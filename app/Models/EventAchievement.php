<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAchievement extends BaseModel
{
    protected $table = 'event_achievements';

    protected $fillable = [
        'achievement_id',
        'source_achievement_id',
        'active_from',
        'active_until',
        'active_through',
    ];

    protected $casts = [
        'active_from' => 'date',
        'active_until' => 'date',
    ];

    protected $appends = [
        'active_through',
    ];

    // == accessors

    public function getActiveThroughAttribute(): ?Carbon
    {
        return $this->active_until ? $this->active_until->clone()->subDays(1) : null;
    }

    // == mutators

    public function setActiveThroughAttribute(Carbon|string|null $value): void
    {
        if (is_string($value)) {
            $value = Carbon::parse($value);
        }

        $this->active_until = $value ? $value->clone()->addDays(1) : null;
    }

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
