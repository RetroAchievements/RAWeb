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

    public const REASON_DEMOTED = 'Demoted';

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
