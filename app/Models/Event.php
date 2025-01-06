<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends BaseModel
{
    protected $table = 'events';

    protected $fillable = [
        'legacy_game_id',
        'image_asset_path',
        'slug'
    ];

    // == accessors

    public function getTitleAttribute(): string
    {
        return $this->game->title;
    }

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    public function getPermalinkAttribute(): string
    {
        // TODO: use slug (implies slug is immutable)
        return $this->game->getPermalinkAttribute();
    }

    // == mutators

    public function setTitleAttribute(string $value): void
    {
        $this->game->title = $value;
    }

    // == relations

    /**
     * @return BelongsTo<Game, Event>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'legacy_game_id', 'ID');
    }

    /**
     * @return HasManyThrough<EventAchievement>
     */
    public function achievements(): HasManyThrough
    {
        return $this->game->hasManyThrough(
            EventAchievement::class,
            Achievement::class,
            'GameID',         // Achievements.GameID
            'achievement_id', // event_achievements.achievement_id
            'ID',             // Game.ID
            'ID',             // Achievement.ID
        )->with('achievement.game');
    }

    // == scopes
}
