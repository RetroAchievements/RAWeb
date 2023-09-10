<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Message extends BaseModel
{
    use SoftDeletes;
    use Searchable;

    // TODO rename Message table to messages
    // TODO rename ID column to id
    // TODO drop UserTo, migrate to recipient_id
    // TODO drop UserFrom, migrate to sender_id
    // TODO rename Title column to title
    // TODO rename Payload column to body
    // TODO rename TimeSent column to sent_at
    // TODO drop Unread, migrate to read_at
    // TODO drop Type
    protected $table = 'Messages';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'TimeSent';
    public const UPDATED_AT = null;

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Title',
            'Payload',
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, Message>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // == scopes

    /**
     * @param Builder<Message> $query
     * @return Builder<Message>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
