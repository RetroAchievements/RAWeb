<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Community\Enums\UserRelationship;
use App\Community\Models\ForumTopicComment;
use App\Community\Models\MessageThreadParticipant;
use App\Community\Models\UserActivity;
use App\Community\Models\UserComment;
use App\Community\Models\UserGameListEntry;
use App\Community\Models\UserRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ActsAsCommunityMember
{
    public static function bootActsAsCommunityMember(): void
    {
    }

    /**
     * @return HasMany<UserActivity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
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

    public function isFollowing(string $username): bool
    {
        return UserRelation::getRelationship($this->User, $username) === UserRelationship::Following;
    }

    public function isBlocking(string $username): bool
    {
        return UserRelation::getRelationship($this->User, $username) === UserRelationship::Blocked;
    }

    public function isForumVerified(): bool
    {
        return !empty($this->forum_verified_at);
    }

    public function isBanned(): bool
    {
        return !empty($this->banned_at);
    }

    public function isNotBanned(): bool
    {
        return !$this->isBanned();
    }

    public function isMuted(): bool
    {
        return !empty($this->muted_until) && $this->muted_until->isFuture();
    }

    public function isNotMuted(): bool
    {
        return !$this->isMuted();
    }

    /**
     * @return MorphMany<UserComment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable')->with('user');
    }

    /**
     * @return HasMany<MessageThreadParticipant>
     */
    public function messageThreadParticipations(): HasMany
    {
        return $this->hasMany(MessageThreadParticipant::class);
    }

    public function getUnreadMessagesCountAttribute(): int
    {
        return (int) ($this->attributes['UnreadMessageCount'] ?? 0);
    }

    /**
     * @return HasMany<ForumTopicComment>
     */
    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumTopicComment::class, 'AuthorID', 'ID');
    }
}
