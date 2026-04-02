<?php

declare(strict_types=1);

namespace App\Community\Enums;

enum LookingForGroupStatus: string
{
    case Active = 'active';
    case Filled = 'filled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Get all valid status transitions.
     * Key is current status, value is array of allowed next statuses.
     */
    public static function getValidTransitions(): array
    {
        return [
            self::Active->value => [
                self::Filled->value,
                self::Cancelled->value,
                self::Expired->value,
            ],
            self::Filled->value => [],
            self::Cancelled->value => [],
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
     * Check if the status is still active.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
