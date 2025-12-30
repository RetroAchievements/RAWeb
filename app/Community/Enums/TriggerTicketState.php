<?php

declare(strict_types=1);

namespace App\Community\Enums;

use InvalidArgumentException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TriggerTicketState: string
{
    case Closed = 'closed';
    case Open = 'open';
    case Resolved = 'resolved';
    case Request = 'request';

    public const REASON_DEMOTED = 'Demoted';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Closed',
            self::Open => 'Open',
            self::Resolved => 'Resolved',
            self::Request => 'Request',
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
     * These values were used when TriggerTicketState was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Closed => 0,
            self::Open => 1,
            self::Resolved => 2,
            self::Request => 3,
        };
    }

    /**
     * Creates a TriggerTicketState from a legacy integer value.
     * Used for backwards compatibility with legacy code that still uses integer values.
     */
    public static function fromLegacyInteger(int $value): self
    {
        return match ($value) {
            0 => self::Closed,
            1 => self::Open,
            2 => self::Resolved,
            3 => self::Request,
            default => throw new InvalidArgumentException("Invalid legacy TriggerTicketState value: {$value}"),
        };
    }
}
