<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Community\Enums\ArticleType;
use App\Community\Enums\UserRelationship;
use App\Models\Comment;
use App\Models\EmailConfirmation;
use App\Models\ForumTopicComment;
use App\Models\MessageThreadParticipant;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserComment;
use App\Models\UserGameListEntry;
use App\Models\UserRelation;
use App\Models\UserUsername;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role as SpatieRole;

// TODO organize accessors, relations, and scopes

trait ActsAsCommunityMember
{
    public static function bootActsAsCommunityMember(): void
    {
    }

    public function getVisibleRoleAttribute(): ?SpatieRole
    {
        // Load the user's displayable roles.
        if ($this->relationLoaded('roles')) {
            /** @var Collection<int, SpatieRole> $displayableRoles */
            $displayableRoles = $this->roles->where('display', '>', 0);
        } else {
            /** @var Collection<int, SpatieRole> $displayableRoles */
            $displayableRoles = $this->displayableRoles()->get();
        }

        // If user has an explicitly set visible_role_id, we'll try to show it.
        // However, we need to verify it's still a valid displayable role for the
        // user (it's possible they lost the role at some point).
        if ($this->visible_role_id !== null) {
            /** @var SpatieRole|null $explicitRole */
            $explicitRole = $displayableRoles->find($this->visible_role_id);
            if ($explicitRole) {
                return $explicitRole;
            }
        }

        // Otherwise, fall back to highest ordered displayable role.
        // For most users, this will return null.
        /** @var SpatieRole|null */
        return $displayableRoles->first();
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
    public function gameListEntries(?string $type = null): HasMany
    {
        $query = $this->hasMany(UserGameListEntry::class, 'user_id', 'ID');

        if ($type !== null) {
            $query->where('SetRequest.type', $type);
        }

        return $query;
    }

    /**
     * @return BelongsToMany<SpatieRole>
     */
    public function displayableRoles(): BelongsToMany
    {
        /** @var BelongsToMany<SpatieRole> */
        return $this->roles()->where('display', '>', 0);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function relatedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, (new UserRelation())->getTable(), 'user_id', 'related_user_id')
            ->withPivot('Friendship'); // TODO rename to `status`
    }

    /**
     * @return BelongsToMany<User>
     */
    public function inverseRelatedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, (new UserRelation())->getTable(), 'related_user_id', 'user_id')
            ->withPivot('Friendship'); // TODO rename to `status`
    }

    /**
     * @return BelongsToMany<User>
     */
    public function followedUsers(): BelongsToMany
    {
        return $this->relatedUsers()->where('Friendship', '=', UserRelationship::Following);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function followerUsers(): BelongsToMany
    {
        return $this->inverseRelatedUsers()->where('Friendship', '=', UserRelationship::Following);
    }

    public function getRelationship(User $user): int
    {
        $relatedUser = $this->relatedUsers()->where('related_user_id', $user->id)->first();

        return $relatedUser ? $relatedUser->pivot->Friendship : UserRelationship::NotFollowing;
    }

    public function isFollowing(User $user): bool
    {
        return $this->getRelationship($user) === UserRelationship::Following;
    }

    public function isBlocking(User $user): bool
    {
        return $this->getRelationship($user) === UserRelationship::Blocked;
    }

    public function isFriendsWith(User $user): bool
    {
        return $this->isFollowing($user) && $user->isFollowing($this);
    }

    public function isEmailVerified(): bool
    {
        return !empty($this->email_verified_at);
    }

    public function isForumVerified(): bool
    {
        return !empty($this->forum_verified_at);
    }

    public function isUnranked(): bool
    {
        return !empty($this->unranked_at);
    }

    public function isRanked(): bool
    {
        return !$this->isUnranked();
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
        return $this->muted_until?->isFuture() ?? false;
    }

    public function isNotMuted(): bool
    {
        return !$this->isMuted();
    }

    public function getIsMutedAttribute(): bool
    {
        return $this->isMuted();
    }

    public function getIsUnrankedAttribute(): bool
    {
        return $this->isUnranked();
    }

    public function getIsBannedAttribute(): bool
    {
        return $this->isBanned();
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<UserComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(UserComment::class, 'ArticleID')->where('ArticleType', ArticleType::User);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<UserComment>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment>
     */
    public function moderationComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::UserModeration);
    }

    /**
     * @return HasMany<EmailConfirmation>
     */
    public function emailConfirmations(): HasMany
    {
        return $this->hasMany(EmailConfirmation::class, 'user_id', 'ID');
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
        return $this->hasMany(ForumTopicComment::class, 'author_id', 'ID');
    }

    /**
     * @return HasMany<Subscription>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'ID');
    }

    /**
     * @return HasMany<UserUsername>
     */
    public function usernameRequests(): HasMany
    {
        return $this->hasMany(UserUsername::class, 'user_id', 'ID');
    }
}
