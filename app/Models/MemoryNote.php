<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class MemoryNote extends BaseModel
{
    use SoftDeletes;

    // TODO rename CodeNotes table to memory_notes
    // TODO rename Address column to address, remove getAddressAttribute()
    // TODO rename Note column to body, remove getBodyAttribute()
    // TODO rename Created column to created_at, remove getCreatedAtAttribute()
    // TODO rename Updated column to updated_at, remove getUpdatedAtAttribute()
    // TODO drop GameID, migrate to game_hash_set_id
    protected $table = 'CodeNotes';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'user_id',
        'GameID',
        'Address',
        'Note',
    ];

    protected $visible = [
        'GameID',
        'Address',
        'Note',
    ];

    // == accessors

    // TODO remove after Address renamed to address
    public function getAddressAttribute(): int
    {
        return $this->attributes['Address'];
    }

    // TODO remove after Note renamed to body
    public function getBodyAttribute(): ?string
    {
        return $this->attributes['Note'];
    }

    // TODO remove after Created renamed to created_at
    public function getCreatedAtAttribute(): ?Carbon
    {
        return $this->attributes['Created'] ? Carbon::parse($this->attributes['Created']) : null;
    }

    // TODO remove after Updated renamed to updated_at
    public function getUpdatedAtAttribute(): ?Carbon
    {
        return $this->attributes['Updated'] ? Carbon::parse($this->attributes['Updated']) : null;
    }

    // == mutators

    // == relations

    /**
     * TODO will need to be modified if GameID is migrated to game_hash_set_id
     *
     * @return BelongsTo<Game, MemoryNote>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID', 'ID');
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
