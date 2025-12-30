<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Community\Enums\TriggerTicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTriggerTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ticketableModel' => 'required|string|in:achievement', // TODO or in:leaderboard
            'ticketableId' => 'required|integer|exists:Achievements,ID', // TODO could also be a leaderboard id
            'mode' => 'required|string|in:hardcore,softcore',
            'issue' => ['required', new Enum(TriggerTicketType::class)],
            'description' => 'required|string|max:2000',
            'emulator' => 'required|string',
            'emulatorVersion' => 'nullable|string',
            'core' => 'nullable|string',
            'gameHashId' => 'required|integer',
            'extra' => 'nullable|string',
        ];
    }
}
