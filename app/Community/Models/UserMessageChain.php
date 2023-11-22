<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class UserMessageChain extends BaseModel
{
    protected $table = 'user_message_chains';

    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'sender_id',
        'recipient_id',
    ];
    
    protected $casts = [
        'sender_deleted_at' => 'datetime',
        'recipient_deleted_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, UserMessageChain>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, UserMessageChain>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // == scopes
}
