<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\TicketListScope;
use Illuminate\Validation\Rules\Enum;

class TicketListApiRequest extends TicketListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'scope' => ['sometimes', 'string', new Enum(TicketListScope::class)],
            'game' => 'required_if:scope,' . TicketListScope::Game->value . '|integer',
            'achievement' => 'required_if:scope,' . TicketListScope::Achievement->value . '|integer',
            'user' => 'required_if:scope,' . implode(',', [
                TicketListScope::AssignedTo->value,
                TicketListScope::ReportedBy->value,
                TicketListScope::AwaitingReporter->value,
                TicketListScope::ResolvedBy->value,
            ]) . '|integer',
        ]);
    }
}
