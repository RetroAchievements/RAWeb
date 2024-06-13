<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class MessageThread extends BaseModel
{
    protected $table = 'message_threads';

    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasMany<MessageThreadParticipant>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(MessageThreadParticipant::class, 'thread_id');
    }

    /**
     * @return HasManyThrough<User>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            MessageThreadParticipant::class,
            'thread_id',
            'ID',
            'id',
            'user_id'
        );
    }

    // == scopes
}
