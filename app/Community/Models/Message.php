<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Message extends BaseModel
{
    use Searchable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'thread_id',
        'author_id',
        'body',
        'created_at',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'body',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Message>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // == scopes
}
