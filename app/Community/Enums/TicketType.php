<?php

declare(strict_types=1);

namespace App\Community\Enums;

use InvalidArgumentException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketType: string
{
    case TriggeredAtWrongTime = 'triggered_at_wrong_time';
    case DidNotTrigger = 'did_not_trigger';

    public function label(): string
    {
        return match ($this) {
            self::TriggeredAtWrongTime => 'Triggered at the wrong time',
            self::DidNotTrigger => 'Did not trigger',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when TicketType was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::TriggeredAtWrongTime => 1,
            self::DidNotTrigger => 2,
        };
    }

    /**
     * Creates a TicketType from a legacy integer value.
     * Used for backwards compatibility with legacy code that still uses integer values.
     */
    public static function fromLegacyInteger(int $value): self
    {
        return match ($value) {
            1 => self::TriggeredAtWrongTime,
            2 => self::DidNotTrigger,
            default => throw new InvalidArgumentException("Invalid legacy TicketType value: {$value}"),
        };
    }
}
