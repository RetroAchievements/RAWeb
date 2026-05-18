<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims;

use App\Models\AchievementSetClaim;

class AchievementSetClaimPresenter
{
    public function __construct(
        private readonly AchievementSetClaim $claim,
    ) {
    }

    public static function for(AchievementSetClaim $claim): self
    {
        return new self($claim);
    }

    /**
     * The underlying enum stores values like "in_review". The public API
     * exposes them in kebab-case for consistency with the rest of V2.
     */
    public function status(): string
    {
        return str_replace('_', '-', $this->claim->status->value);
    }

    public function claimType(): string
    {
        return $this->claim->claim_type->value;
    }

    public function setType(): string
    {
        return str_replace('_', '-', $this->claim->set_type->value);
    }

    public function specialType(): string
    {
        return str_replace('_', '-', $this->claim->special_type->value);
    }

    public function userId(): ?string
    {
        return $this->claim->user?->ulid;
    }

    public function userDisplayName(): ?string
    {
        return $this->claim->user?->display_name;
    }

    public function gameId(): ?int
    {
        return $this->claim->game?->id;
    }

    public function gameTitle(): ?string
    {
        return $this->claim->game?->title;
    }

    public function gameIconUrl(): ?string
    {
        return $this->claim->game?->badge_url;
    }

    public function systemId(): ?int
    {
        return $this->claim->game?->system?->id;
    }

    public function systemName(): ?string
    {
        return $this->claim->game?->system?->name;
    }

    /**
     * Minutes between now and the claim's `finished_at`. Negative when expired,
     * positive when still active. Computed in PHP, not sortable.
     */
    public function minutesLeft(): int
    {
        return (int) round(now()->diffInMinutes($this->claim->finished_at, false));
    }
}
