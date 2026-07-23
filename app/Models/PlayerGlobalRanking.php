<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerGlobalRankingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerGlobalRanking extends BaseModel
{
    /** @use HasFactory<PlayerGlobalRankingFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'window',
        'mode',
        'achievements_unlocked',
        'points',
        'points_weighted',
        'awards_count',
        'rank_number',
        'weighted_rank_number',
    ];

    protected $casts = [
        'achievements_unlocked' => 'integer',
        'points' => 'integer',
        'points_weighted' => 'integer',
        'awards_count' => 'integer',
        'rank_number' => 'integer',
        'weighted_rank_number' => 'integer',
        'window' => GlobalRankingWindow::class,
        'mode' => GlobalRankingMode::class,
    ];

    protected static function newFactory(): PlayerGlobalRankingFactory
    {
        return PlayerGlobalRankingFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes
}
