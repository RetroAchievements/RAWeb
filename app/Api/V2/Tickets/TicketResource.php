<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Api\V2\BaseJsonApiResource;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property Ticket $resource
 */
class TicketResource extends BaseJsonApiResource
{
    /**
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $presenter = TicketPresenter::for($this->resource);

        return [
            'state' => $this->resource->state->value,
            'type' => $this->resource->type->value,
            'body' => $this->resource->body,
            'hardcore' => $this->resource->hardcore !== null ? (bool) $this->resource->hardcore : null,

            'reportedAt' => $this->resource->created_at,
            'resolvedAt' => $this->resource->resolved_at,

            'ticketableType' => $this->resource->ticketable_type,
            'ticketableId' => $this->resource->ticketable_id,

            // embedded context so consumers can render lists without a forced ?include
            'ticketableTitle' => $presenter->ticketableTitle(),
            'gameId' => $presenter->gameId(),
            'gameTitle' => $presenter->gameTitle(),
            'gameIconUrl' => $presenter->gameIconUrl(),
            'systemName' => $presenter->systemName(),
            'reporterDisplayName' => $presenter->reporterDisplayName(),
            'resolverDisplayName' => $presenter->resolverDisplayName(),
            'authorDisplayName' => $presenter->authorDisplayName(),
        ];
    }

    /**
     * Emit only the polymorphic side matching `ticketable_type` (the underlying
     * BelongsTo relations are unguarded). Reporter, resolver, author are always
     * candidates. Every relationship is still gated by `?include=`.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        $candidates = ['reporter', 'resolver', 'author'];
        $matchingTicketable = match ($this->resource->ticketable_type) {
            TicketableType::Achievement->value => 'achievement',
            TicketableType::Leaderboard->value => 'leaderboard',
            default => null,
        };
        if ($matchingTicketable !== null) {
            $candidates[] = $matchingTicketable;
        }

        $relationships = [];
        foreach ($candidates as $key) {
            if ($this->wasIncluded($request, $key)) {
                $relationships[$key] = $this->relation($key)->withoutLinks()->showDataIfLoaded();
            }
        }

        return $relationships;
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        $webLink = new Link('webUrl', route('ticket.show', ['ticket' => $this->resource->id]));

        return new Links(...array_filter([$this->selfLink(), $webLink]));
    }
}
