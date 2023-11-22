<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class UserMessage extends BaseModel
{
    protected $table = 'user_messages';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'chain_id',
        'author_id',
        'body',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, UserMessage>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // == scopes
}
