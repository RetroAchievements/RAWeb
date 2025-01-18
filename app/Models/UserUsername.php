<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUsername extends BaseModel
{
    protected $table = 'user_usernames';

    protected $fillable = [
        'user_id',
        'username',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Username change requests that are more than 30 days old are naturally
        // filtered out of all requests. These are considered "expired".
        static::addGlobalScope('hideStaleRequests', function (Builder $builder) {
            $builder->where(function ($query) {
                $query->whereNotNull('approved_at')
                    ->orWhere('created_at', '>', now()->subDays(30));
            });
        });
    }

    // == accessors

    public function getIsApprovedAttribute(): bool
    {
        return $this->approved_at !== null;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, UserUsername>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes

    /**
     * @param Builder<UserUsername> $query
     * @return Builder<UserUsername>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_at');
    }
}
