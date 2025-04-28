<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Message extends BaseModel
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;
    use Searchable;

    protected $table = 'messages';

    public const UPDATED_AT = null;

    protected $fillable = [
        'thread_id',
        'author_id',
        'body',
        'created_at',
    ];

    protected $casts = [
        'Unread' => 'boolean',
    ];

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

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
        return $this->belongsTo(User::class, 'author_id', 'ID')->withTrashed();
    }

    // == scopes
}
