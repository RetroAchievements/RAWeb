<?php

declare(strict_types=1);

namespace App\Community\Enums;

use App\Platform\Enums\TicketableType;
use InvalidArgumentException;
use LogicException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketType: string
{
    case DidNotCancel = 'did_not_cancel';
    case DidNotStart = 'did_not_start';
    case DidNotSubmit = 'did_not_submit';
    case DidNotTrigger = 'did_not_trigger';
    case SubmittedWrongValue = 'submitted_wrong_value';
    case TriggeredAtWrongTime = 'triggered_at_wrong_time';

    public function appliesTo(TicketableType $type): bool
    {
        return match ($this) {
            self::TriggeredAtWrongTime, self::DidNotTrigger => $type === TicketableType::Achievement,
            self::DidNotStart, self::DidNotCancel, self::DidNotSubmit, self::SubmittedWrongValue => $type === TicketableType::Leaderboard,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DidNotCancel => 'Did not cancel',
            self::DidNotStart => 'Did not start',
            self::DidNotSubmit => 'Did not submit',
            self::DidNotTrigger => 'Did not trigger',
            self::SubmittedWrongValue => 'Submitted wrong value',
            self::TriggeredAtWrongTime => 'Triggered at the wrong time',
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

            self::DidNotStart,
            self::DidNotCancel,
            self::DidNotSubmit,
            self::SubmittedWrongValue => throw new LogicException("TicketType {$this->value} has no legacy integer mapping."),
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
