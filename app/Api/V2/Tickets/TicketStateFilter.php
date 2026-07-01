<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Community\Enums\TicketState;

final class TicketStateFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'state';
    }

    protected function column(): string
    {
        return 'state';
    }

    protected function enumClass(): string
    {
        return TicketState::class;
    }

    protected function errorLabel(): string
    {
        return 'ticket state';
    }
}
