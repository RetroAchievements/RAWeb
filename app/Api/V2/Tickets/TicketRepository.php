<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Eloquent\Contracts\Driver;
use LaravelJsonApi\Eloquent\Contracts\Parser;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Schema;

class TicketRepository extends Repository
{
    private Parser $ticketParser;
    private Schema $ticketSchema;

    public function __construct(Schema $schema, Driver $driver, Parser $parser)
    {
        parent::__construct($schema, $driver, $parser);
        $this->ticketParser = $parser;
        $this->ticketSchema = $schema;
    }

    public function find(string $resourceId): ?object
    {
        /** @var User|null $caller */
        $caller = Auth::user();

        $ticket = Ticket::query()
            ->with($this->ticketSchema->with())
            ->visibleTo($caller)
            ->find($resourceId);

        return $ticket ? $this->ticketParser->parseNullable($ticket) : null;
    }

    public function exists(string $resourceId): bool
    {
        /** @var User|null $caller */
        $caller = Auth::user();

        return Ticket::query()
            ->visibleTo($caller)
            ->whereKey($resourceId)
            ->exists();
    }
}
