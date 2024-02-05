<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoryNote extends BaseModel
{
    use SoftDeletes;

    // TODO rename CodeNotes table to memory_notes
    // TODO rename Address column to address
    // TODO rename AuthorID column to user_id
    // TODO rename Note column to body
    // TODO rename Created column to created_at
    // TODO rename Updated column to updated_at
    // TODO drop GameID, migrate to game_hash_set_id
    protected $table = 'CodeNotes';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'AuthorID',
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

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, MemoryNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // == scopes
}
