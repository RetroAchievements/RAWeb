<?php

declare(strict_types=1);

namespace App\Models;

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
        'slug',
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
                'slug',
                'active_from',
                'active_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

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
        return $this->legacyGame->hasManyThrough(
            EventAchievement::class,
            Achievement::class,
            'GameID',         // Achievements.GameID
            'achievement_id', // event_achievements.achievement_id
            'ID',             // GameData.ID
            'ID',             // Achievements.ID
        )->with('achievement.game');
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
