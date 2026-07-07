<?php

declare(strict_types=1);

namespace App\Api\V2\UserFollows;

use App\Models\User;
use App\Models\UserRelation;

class UserFollowPresenter
{
    /**
     * @param array<int, bool> $reciprocalUserIds
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
        return $this->user()?->ulid;
    }

    public function displayName(): ?string
    {
        return $this->user()?->display_name;
    }

    public function avatarUrl(): ?string
    {
        return $this->user()?->avatarUrl;
    }

    public function points(): int
    {
        return $this->user()?->points ?? 0;
    }

    public function pointsHardcore(): int
    {
        return $this->user()?->points_hardcore ?? 0;
    }

    public function isMutual(): bool
    {
        $otherUserId = $this->user()?->id;
        if ($otherUserId === null) {
            return false;
        }

        return isset($this->reciprocalUserIds[$otherUserId]);
    }

    public function user(): ?User
    {
        return $this->relation->user_id === $this->perspective->id
            ? $this->relation->relatedUser
            : $this->relation->user;
    }
}
