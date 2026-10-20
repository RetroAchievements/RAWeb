<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Community\Enums\TicketAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::enum(TicketAction::class)],
        ];
    }
}
