<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\GameActivitySnapshotType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameActivitySnapshotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameActivitySnapshot extends BaseModel
{
    /** @use HasFactory<GameActivitySnapshotFactory> */
    use HasFactory;
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'game_id',
        'type',
        'score',
        'player_count',
        'trend_multiplier',
        'trending_reason',
        'meta',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'trend_multiplier' => 'decimal:2',
        'meta' => 'array',
    ];

    protected static function newFactory(): GameActivitySnapshotFactory
    {
        return GameActivitySnapshotFactory::new();
    }

    /**
     * @return Builder<GameActivitySnapshot>
     */
    public function prunable(): Builder
    {
        // TODO eventually lower this to 7 days. it's 30 right now because i want to analyze trends in prod
        // for more potential improvements / fix anything that potentially goes wrong.
        return $this->where('created_at', '<', now()->subDays(30));
    }

    // == scopes

    /**
     * @param Builder<GameActivitySnapshot> $query
     * @return Builder<GameActivitySnapshot>
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('type', GameActivitySnapshotType::Trending);
    }

    /**
     * @param Builder<GameActivitySnapshot> $query
     * @return Builder<GameActivitySnapshot>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->where('type', GameActivitySnapshotType::Popular);
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
