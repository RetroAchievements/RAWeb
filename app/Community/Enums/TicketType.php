<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketType')]
abstract class TicketType
{
    public const TriggeredAtWrongTime = 1;

    public const DidNotTrigger = 2;

    public static function toString(int $type): string
    {
        return match ($type) {
            TicketType::DidNotTrigger => "Did not trigger",
            TicketType::TriggeredAtWrongTime => "Triggered at the wrong time",
            default => "Invalid ticket type",
        };
    }

    public static function cases(): array
    {
        return [
            self::TriggeredAtWrongTime,
            self::DidNotTrigger,
        ];
    }
}
