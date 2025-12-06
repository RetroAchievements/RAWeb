<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\PlayerStatRankingKind;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerStatRankingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStatRanking extends BaseModel
{
    /** @use HasFactory<PlayerStatRankingFactory> */
    use HasFactory;

    protected $table = 'player_stat_rankings';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'system_id',
        'kind',
        'total',
        'rank_number',
        'row_number',
        'last_game_id',
        'last_affected_at',
    ];

    protected $casts = [
        'kind' => PlayerStatRankingKind::class,
        'last_affected_at' => 'datetime',
    ];

    protected static function newFactory(): PlayerStatRankingFactory
    {
        return PlayerStatRankingFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function lastGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'last_game_id');
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes
}
