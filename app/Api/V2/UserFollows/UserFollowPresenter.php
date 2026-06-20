<?php

declare(strict_types=1);

namespace App\Api\V2\UserFollows;

use App\Models\User;
use App\Models\UserRelation;

class UserFollowPresenter
{
    /**
     * @param array<int, bool> $reciprocalUserIds Lookup map (id => true) of users
     *        reciprocating $perspective on the opposite side. Map (not list) so
     *        isMutual() is O(1).
     */
    public function __construct(
        private readonly UserRelation $relation,
        private readonly User $perspective,
        private readonly array $reciprocalUserIds,
    ) {
    }

    /**
     * @param array<int, bool> $reciprocalUserIds
     */
    public static function for(UserRelation $relation, User $perspective, array $reciprocalUserIds): self
    {
        return new self($relation, $perspective, $reciprocalUserIds);
    }

    public function userId(): ?string
    {
        return $this->otherUser()?->ulid;
    }

    public function displayName(): ?string
    {
        return $this->otherUser()?->display_name;
    }

    public function avatarUrl(): ?string
    {
        return $this->otherUser()?->avatarUrl;
    }

    public function points(): int
    {
        return $this->otherUser()?->points ?? 0;
    }

    public function pointsHardcore(): int
    {
        return $this->otherUser()?->points_hardcore ?? 0;
    }

    public function isMutual(): bool
    {
        $otherUserId = $this->otherUser()?->id;
        if ($otherUserId === null) {
            return false;
        }

        return isset($this->reciprocalUserIds[$otherUserId]);
    }

    private function otherUser(): ?User
    {
        return $this->relation->user_id === $this->perspective->id
            ? $this->relation->relatedUser
            : $this->relation->user;
    }
}
