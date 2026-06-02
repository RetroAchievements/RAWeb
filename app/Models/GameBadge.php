<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameBadgeAttribution;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameBadgeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameBadge extends BaseModel
{
    /** @use HasFactory<GameBadgeFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'game_badges';

    /**
     * SHA1 hashes of placeholder badge content. We match on content rather than path
     * because a dev removing a badge would re-upload the placeholder image, producing a
     * brand new /Images/NNNNNN.png that is byte-identical to the placeholder but carries
     * its own unique filename. Path-based detection alone would miss those re-uploads.
     */
    public const PLACEHOLDER_BADGE_SHA1S = [
        '822169dcf0c2fbf293975881ee5890de9148bdfa', // gamepad placeholder
        'e92ab7425e301a1320bda861def13959b69d98d9', // another gamepad placeholder
        '37b1131b6a980410f86930da7de6e08ab3867005', // solid black placeholder
        '6a8ececcb019f7048a2195e9265bbf8a84f4158e', // "under construction" placeholder
        'ee7b886701810faaae44fd845e2d7d07e65ba663', // current icon-safe.png
    ];

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

    // == helpers

    public static function isPlaceholderPath(string $path): bool
    {
        return in_array($path, [Game::PLACEHOLDER_BADGE_PATH, Game::PLACEHOLDER_IMAGE_PATH], true);
    }

    public static function isPlaceholderSha1(string $sha1): bool
    {
        return in_array($sha1, self::PLACEHOLDER_BADGE_SHA1S, true);
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
