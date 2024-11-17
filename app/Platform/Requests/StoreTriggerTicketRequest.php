<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTriggerTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ticketableModel' => 'required|string|in:achievement', // TODO or in:leaderboard
            'ticketableId' => 'required|integer|exists:Achievements,ID', // TODO could also be a leaderboard id
            'mode' => 'required|string|in:hardcore,softcore',
            'issue' => 'required|integer|min:1|max:2', // see `TicketType`
            'description' => 'required|string|max:2000',
            'emulator' => 'required|string',
            'emulatorVersion' => 'nullable|string',
            'core' => 'nullable|string',
            'gameHashId' => 'required|integer',
            'extra' => 'nullable|string',
        ];
    }
}
