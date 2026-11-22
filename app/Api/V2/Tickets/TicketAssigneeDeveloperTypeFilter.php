<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class TicketAssigneeDeveloperTypeFilter implements Filter
{
    public function key(): string
    {
        return 'assigneeDeveloperType';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function apply($query, $value)
    {
        $raw = trim(is_array($value) ? implode(',', $value) : (string) $value);

        if (str_contains($raw, ',')) {
            throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "The assigneeDeveloperType filter accepts one value [{$raw}].",
            ]);
        }

        return match ($raw) {
            '', 'all' => $query,
            'active' => $query->assignedToActiveDeveloper(),
            'junior' => $query->assignedToJuniorDeveloper(),
            'inactive' => $query->assignedToInactiveDeveloper(),
            default => throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Unknown assignee developer type [{$raw}].",
            ]),
        };
    }
}
