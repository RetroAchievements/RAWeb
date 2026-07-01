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

    protected function enumClass(): string
    {
        return TicketType::class;
    }

    protected function errorLabel(): string
    {
        return 'ticket type';
    }
}
