<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Community\Enums\TicketResolution;

final class TicketResolutionFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'resolution';
    }

    protected function column(): string
    {
        return 'resolution';
    }

    protected function resolveValue(string $value): ?string
    {
        return TicketResolution::tryFrom($value)?->value;
    }

    protected function errorLabel(): string
    {
        return 'ticket resolution';
    }
}
