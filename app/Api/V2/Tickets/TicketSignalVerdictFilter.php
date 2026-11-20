<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Community\Enums\TicketSignalVerdict;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

final class TicketSignalVerdictFilter extends AbstractTicketEnumFilter
{
    public function key(): string
    {
        return 'signalVerdict';
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function apply($query, $value)
    {
        if (!request()->user()?->can('viewTriageSignals', Ticket::class)) {
            throw JsonApiException::error([
                'status' => '403',
                'code' => 'forbidden_filter',
                'title' => 'Forbidden Filter',
                'detail' => 'Only developers can filter tickets by signal verdict.',
            ]);
        }

        return parent::apply($query, $value);
    }

    protected function column(): string
    {
        return 'current_signal_verdict';
    }

    protected function resolveValue(string $value): ?string
    {
        return TicketSignalVerdict::tryFrom($value)?->value;
    }

    protected function errorLabel(): string
    {
        return 'ticket signal verdict';
    }

    protected function nullKeyword(): string
    {
        return 'none';
    }
}
