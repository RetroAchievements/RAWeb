<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Community\Models\Message;
use App\Community\Models\UserActivity;
use App\Community\Models\UserActivityLegacy;
use App\Community\Models\UserComment;
use App\Community\Models\UserGameListEntry;
use App\Site\Models\User;
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

    /**
     * @return HasMany<UserActivityLegacy>
     */
    public function activities(): HasMany
    {
        // TODO return $this->hasMany(UserActivity::class);
        return $this->hasMany(UserActivityLegacy::class, 'User', 'User');
    }

    /**
     * @return HasMany<UserGameListEntry>
     */
    public function gameList(string $type): HasMany
    {
        return $this->hasMany(UserGameListEntry::class, 'user_id', 'ID')
            ->where('SetRequest.type', $type);
    }

    /**
     * @return BelongsTo<UserActivity, User>
     */
    public function lastActivity(): BelongsTo
    {
        /*
         * dynamic relationship
         */
        return $this->belongsTo(UserActivity::class, 'last_activity_id');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'Friends', 'user_id', 'related_user_id');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'Friends', 'related_user_id', 'user_id');
    }

    /**
     * @return MorphMany<UserComment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable')->with('user');
    }

    /**
     * @return HasMany<Message>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * @return HasMany<Message>
     */
    public function unreadMessages(): HasMany
    {
        return $this->messages()->unread();
    }

    public function getUnreadMessagesCountAttribute(): int
    {
        return (int) ($this->attributes['unread_messages_count'] ?? 0);
    }

    /**
     * @param Builder<User> $query
     */
    public function scopeWithLastActivity(Builder $query): void
    {
        $query->addSelect([
            'last_activity_id' => function ($query) {
                /* @var Builder $query */
                $query->select('user_activities.id')
                    ->from('user_activities')
                    ->whereColumn('user_activities.user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->limit(1);
            },
        ])->with('lastActivity');
    }
}
