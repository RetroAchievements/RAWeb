<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConnection extends BaseModel
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'token',
        'token_secret',
        'refresh_token',
        'expires',
        'nickname',
        'name',
        'email',
        'avatar',
        'url',
    ];

    protected $hidden = [
        'token',
        'token_secret',
        'refresh_token',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, UserConnection>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
