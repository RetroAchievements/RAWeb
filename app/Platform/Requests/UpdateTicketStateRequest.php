<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Community\Enums\TicketAction;
use App\Community\Requests\StoreCommentRequest;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        return [
            'action' => ['required', 'string', Rule::enum(TicketAction::class)],
            'note' => [
                'nullable',
                'required_if:action,' . TicketAction::ClosedOther->value,
                ...StoreCommentRequest::bodyRules(),
            ],
            'coveringTicketId' => [
                'nullable',
                'required_if:action,' . TicketAction::CoveredByAnotherTicket->value,
                'integer',
                Rule::exists('tickets', 'id')->whereNull('deleted_at'),
                Rule::notIn([$ticket->id]),
            ],
        ];
    }
}
