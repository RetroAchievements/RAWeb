<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameBadgeAttribution;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameBadgeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameBadge extends BaseModel
{
    /** @use HasFactory<GameBadgeFactory> */
    use HasFactory;

    protected $table = 'game_badges';

    protected $fillable = [
        'game_id',
        'image_asset_path',
        'sha1',
        'attribution_source',
        'uploaded_by_user_id',
        'became_current_at',
        'replaced_at',
    ];

    protected $casts = [
        'attribution_source' => GameBadgeAttribution::class,
        'became_current_at' => 'datetime',
        'replaced_at' => 'datetime',
    ];

    protected static function newFactory(): GameBadgeFactory
    {
        return GameBadgeFactory::new();
    }

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    // == scopes

    /**
     * @param Builder<GameBadge> $query
     * @return Builder<GameBadge>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('replaced_at');
    }

    /**
     * @param Builder<GameBadge> $query
     * @return Builder<GameBadge>
     */
    public function scopeHistorical(Builder $query): Builder
    {
        return $query->whereNotNull('replaced_at');
    }
}
