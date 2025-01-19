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
        'denied_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
    ];

    // == accessors

    public function getIsApprovedAttribute(): bool
    {
        return $this->approved_at !== null;
    }

    public function getIsDeniedAttribute(): bool
    {
        return $this->denied_at !== null;
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->approved_at !== null || $this->denied_at !== null) {
            return false;
        }

        return $this->created_at->isPast() && $this->created_at->diffInDays(now()) >= 30;
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
    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * @param Builder<UserUsername> $query
     * @return Builder<UserUsername>
     */
    public function scopeDenied(Builder $query): Builder
    {
        return $query->whereNotNull('denied_at')
            ->where('denied_at', '>', now()->subDays(30));
    }

    /**
     * @param Builder<UserUsername> $query
     * @return Builder<UserUsername>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('approved_at')
            ->whereNull('denied_at')
            ->where('created_at', '<=', now()->subDays(30));
    }

    /**
     * @param Builder<UserUsername> $query
     * @return Builder<UserUsername>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_at')
            ->whereNull('denied_at')
            ->where('created_at', '>', now()->subDays(30));
    }
}
