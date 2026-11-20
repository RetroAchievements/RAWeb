<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Community\Enums\TicketType;

final class TicketTypeFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'type';
    }

    protected function column(): string
    {
        return 'type';
    }

    protected function resolveValue(string $value): ?string
    {
        return TicketType::tryFrom($value)?->value;
    }

    protected function errorLabel(): string
    {
        return 'ticket type';
    }
}
