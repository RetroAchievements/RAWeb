<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

final class TicketHardcoreFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'hardcore';
    }

    protected function column(): string
    {
        return 'hardcore';
    }

    protected function resolveValue(string $value): ?int
    {
        return match ($value) {
            'true' => 1,
            'false' => 0,
            default => null,
        };
    }

    protected function errorLabel(): string
    {
        return 'hardcore value';
    }

    protected function nullKeyword(): string
    {
        return 'unspecified';
    }
}
