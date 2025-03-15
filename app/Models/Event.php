<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\EventState;
use App\Support\Database\Eloquent\BaseModel;
use Carbon\Carbon;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends BaseModel
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'events';

    protected $fillable = [
        'legacy_game_id',
        'image_asset_path',
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

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'image_asset_path',
                'active_from',
                'active_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    public function getStateAttribute(): EventState
    {
        $now = now();

        /**
         * An event is active if:
         * - The conclusion date is in the future, or
         * - Any of the event achievements have an activeUntil date in the future.
         */
        if (
            $this->active_through?->isAfter($now)
            || $this->achievements->some(fn ($a) => $a->active_until?->isAfter($now))
        ) {
            return EventState::Active;
        }

        /**
         * An event is evergreen (never expires) if:
         * - Every event achievement has a null activeUntil value, meaning they can be earned indefinitely.
         */
        if ($this->achievements->every(fn ($a) => !$a->active_until)) {
            return EventState::Evergreen;
        }

        /**
         * An event is concluded if:
         * - The event's end date is before the current date, and
         * - Every event achievement has an activeUntil date which is before the current date.
         */
        if (
            $this->active_through?->isBefore($now)
            && $this->achievements->every(fn ($a) => $a->active_until?->isBefore($now))
        ) {
            return EventState::Concluded;
        }

        // This shouldn't happen.
        return EventState::Concluded;
    }

    public function getTitleAttribute(): string
    {
        return $this->legacyGame->title;
    }

    public function getActiveThroughAttribute(): ?Carbon
    {
        return $this->active_until ? $this->active_until->clone()->subDays(1) : null;
    }

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    public function getPermalinkAttribute(): string
    {
        // TODO: use slug (implies slug is immutable)
        return $this->legacyGame->getPermalinkAttribute();
    }

    // == mutators

    public function setTitleAttribute(string $value): void
    {
        $this->legacyGame->title = $value;
    }

    public function setActiveThroughAttribute(Carbon|string|null $value): void
    {
        if (is_string($value)) {
            $value = Carbon::parse($value);
        }

        $this->active_until = $value ? $value->clone()->addDays(1) : null;
    }

    // == relations

    /**
     * @return HasMany<EventAward>
     */
    public function awards(): HasMany
    {
        return $this->hasMany(EventAward::class, 'event_id');
    }

    /**
     * @return BelongsTo<Game, Event>
     */
    public function legacyGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'legacy_game_id', 'ID');
    }

    /**
     * @return HasManyThrough<EventAchievement>
     */
    public function achievements(): HasManyThrough
    {
        return $this->hasManyThrough(
            EventAchievement::class,
            Achievement::class,
            'GameID',         // Achievements.GameID
            'achievement_id', // event_achievements.achievement_id
            'legacy_game_id', // events.legacy_game_id
            'ID',             // Achievements.ID
        )->with('achievement.game');
    }

    /**
     * @return HasManyThrough<EventAchievement>
     */
    public function publishedAchievements(): HasManyThrough
    {
        return $this->achievements()->published();
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function hubs(): BelongsToMany
    {
        return $this->legacyGame->gameSets();
    }

    // == scopes
}
