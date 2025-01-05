<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAward extends BaseModel
{
    protected $table = 'event_awards';

    protected $fillable = [
        'game_id',
        'tier_index',
        'label',
        'achievements_required',
        'image_asset_path',
    ];

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, EventAward>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    // == scopes
}
