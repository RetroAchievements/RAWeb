<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameInviteStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Canceled = 'canceled';
    case Expired = 'expired';

    /**
     * Get all valid status transitions.
     * Key is current status, value is array of allowed next statuses.
     */
    public static function getValidTransitions(): array
    {
        return [
            self::Pending->value => [
                self::Accepted->value,
                self::Declined->value,
                self::Canceled->value,
                self::Expired->value,
            ],
            self::Accepted->value => [],
            self::Declined->value => [],
            self::Canceled->value => [],
            self::Expired->value => [],
        ];
    }

    /**
     * Check if a transition from this status to another is valid.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus->value, self::getValidTransitions()[$this->value] ?? []);
    }

    /**
     * Check if the status is terminal (no further transitions allowed).
     */
    public function isTerminal(): bool
    {
        return empty(self::getValidTransitions()[$this->value] ?? []);
    }

    /**
     * Check if the status is still active/pending.
     */
    public function isActive(): bool
    {
        return $this === self::Pending;
    }
}
