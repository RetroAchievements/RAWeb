<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Carbon\Carbon;
use Database\Factories\EventAchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventAchievement extends BaseModel
{
    /** @use HasFactory<EventAchievementFactory> */
    use HasFactory;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

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

    protected static function newFactory(): EventAchievementFactory
    {
        return EventAchievementFactory::new();
    }

    public const RAEVENTS_USER_ID = 279854;
    public const DEVQUEST_USER_ID = 240336;
    public const QATEAM_USER_ID = 180732;

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'source_achievement_id',
                'active_from',
                'active_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    public function getTitleAttribute(): string
    {
        return $this->achievement->title;
    }

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
     * @return HasOneThrough<Event>
     */
    public function event(): HasOneThrough
    {
        return $this->hasOneThrough(
            Event::class,
            Achievement::class,
            'ID',             // Achievements.ID
            'legacy_game_id', // events.legacy_game_id
            'achievement_id', // event_achievements.achievement_id
            'GameID'          // Achievements.GameID
        );
    }

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
    public function scopeCurrentAchievementOfTheWeek(Builder $query): Builder
    {
        return $query->active()
            ->whereHas('achievement.game', function ($query) { // only from the current AotW event
                $query->where('Title', 'like', '%of the week%');
            })
            ->whereNotNull('active_from')
            ->whereNotNull('active_until')
            ->whereRaw(dateCompareStatement('active_until', 'active_from', '< 20')); // ignore AotM achievements.
    }

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
