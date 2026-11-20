<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Platform\Enums\TicketableType;

final class TicketableTypeFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'ticketableType';
    }

    protected function column(): string
    {
        return 'ticketable_type';
    }

    protected function resolveValue(string $value): ?string
    {
        return TicketableType::tryFrom($value)?->value;
    }

    protected function errorLabel(): string
    {
        return 'ticketable type';
    }
}
