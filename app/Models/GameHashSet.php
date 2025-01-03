<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// currently unused
// TODO HasVersionedTrigger (?)
class GameHashSet extends BaseModel
{
    use SoftDeletes;

    protected $table = 'game_hash_sets';

    public const SCOPE_ALL = [
        'compatible',
    ];

    protected $fillable = [
        'game_id',
        'compatible',
    ];

    // == relations

    /**
     * @return BelongsToMany<GameHash>
     */
    public function hashes(): BelongsToMany
    {
        return $this->belongsToMany(GameHash::class, GameHashSetHash::getFullTableName())
            ->using(GameHashSetHash::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Game, GameHashSet>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return HasMany<MemoryNote>
     */
    public function memoryNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class);
    }

    // == scopes

    /**
     * @param Builder<GameHashSet> $query
     * @return Builder<GameHashSet>
     */
    public function scopeCompatible(Builder $query, bool $compatible = true): Builder
    {
        return $query->where('compatible', $compatible);
    }
}
