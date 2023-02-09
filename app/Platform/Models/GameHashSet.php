<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Platform\Contracts\HasVersionedTrigger;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameHashSet extends BaseModel implements HasVersionedTrigger
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

    public function hashes(): BelongsToMany
    {
        return $this->belongsToMany(GameHash::class, GameHashSetHash::getFullTableName())
            ->using(GameHashSetHash::class)
            ->withTimestamps();
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function memoryNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class);
    }

    public function trigger(): MorphOne
    {
        return $this->morphOne(Trigger::class, 'triggerable')
            ->whereNotNull('version')
            ->orderByDesc('version');
    }

    public function triggers(): MorphToMany
    {
        return $this->morphToMany(Trigger::class, 'triggerable')
            ->orderByDesc('version');
    }

    // == scopes

    public function scopeCompatible(Builder $query, bool $compatible = true): Builder
    {
        return $query->where('compatible', $compatible);
    }
}
