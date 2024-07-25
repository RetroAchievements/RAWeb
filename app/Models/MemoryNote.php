<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoryNote extends BaseModel
{
    use SoftDeletes;

    // TODO drop game_id, migrate to game_hash_set_id
    protected $table = 'memory_notes';

    protected $fillable = [
        'user_id',
        'game_id',
        'address',
        'body',
    ];

    protected $visible = [
        'game_id',
        'address',
        'body',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * TODO will need to be modified if GameID is migrated to game_hash_set_id
     *
     * @return BelongsTo<Game, MemoryNote>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, MemoryNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // == scopes
}
