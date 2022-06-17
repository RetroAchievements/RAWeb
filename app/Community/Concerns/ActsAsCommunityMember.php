<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Community\Models\Message;
use App\Community\Models\UserActivity;
use App\Community\Models\UserComment;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ActsAsCommunityMember
{
    public static function bootActsAsCommunityMember(): void
    {
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    public function lastActivity(): BelongsTo
    {
        /*
         * dynamic relationship
         */
        return $this->belongsTo(UserActivity::class, 'last_activity_id');
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_relations', 'user_id', 'related_user_id')
            ->using(BasePivot::class)
            /*
             * alias id to users' id column to resolve ambiguity
             */
            ->select(['id' => 'users.id']);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable')->with('user');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function unreadMessages(): HasMany
    {
        return $this->messages()->unread();
    }

    public function getUnreadMessagesCountAttribute(): int
    {
        return (int) ($this->attributes['unread_messages_count'] ?? 0);
    }

    public function scopeWithLastActivity(Builder $query): void
    {
        $query->addSelect([
            'last_activity_id' => function ($query) {
                /* @var Builder $query */
                $query->select('user_activity_log.id')
                    ->from('user_activity_log')
                    ->whereColumn('user_activity_log.user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->limit(1);
            },
        ])->with('lastActivity');
    }
}
