<?php

declare(strict_types=1);

namespace App\Community\Enums;

use InvalidArgumentException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketState: string
{
    case Closed = 'closed';
    case Open = 'open';
    case Resolved = 'resolved';
    case Request = 'request';
    case Quarantined = 'quarantined';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Closed',
            self::Open => 'Open',
            self::Resolved => 'Resolved',
            self::Request => 'Request',
            self::Quarantined => 'Pending Review',
        };
    }

    public function isOpen(): bool
    {
        return match ($this) {
            self::Open, self::Request => true,
            default => false,
        };
    }

    /**
     * The rank used when any ticket list sorts by a specific state value.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::Open => 0,
            self::Request => 1,
            self::Quarantined => 2,
            self::Resolved => 3,
            self::Closed => 4,
        };
    }

    /**
     * @see 2026_09_11_152324_add_state_sort_order_to_tickets_table.php
     *
     * When adding a new status, be sure to include a migration that also
     * recreates `state_sort_order`.
     */
    public static function sortOrderSqlExpression(): string
    {
        $states = self::cases();
        usort($states, fn (self $a, self $b) => $a->sortOrder() <=> $b->sortOrder());
        $cases = implode(' ', array_map(
            fn (self $state) => "WHEN '{$state->value}' THEN {$state->sortOrder()}",
            $states,
        ));

        return "CASE state {$cases} ELSE " . count(self::cases()) . ' END';
    }

    public function isResolved(): bool
    {
        return match ($this) {
            self::Resolved, self::Closed => true,
            default => false,
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when TicketState was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Closed => 0,
            self::Open => 1,
            self::Resolved => 2,
            self::Request => 3,
            self::Quarantined => 4,
        };
    }

    /**
     * Creates a TicketState from a legacy integer value.
     * Used for backwards compatibility with legacy code that still uses integer values.
     */
    public static function fromLegacyInteger(int $value): self
    {
        return match ($value) {
            0 => self::Closed,
            1 => self::Open,
            2 => self::Resolved,
            3 => self::Request,
            4 => self::Quarantined,
            default => throw new InvalidArgumentException("Invalid legacy TicketState value: {$value}"),
        };
    }
}
