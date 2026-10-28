<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Community\Enums\TicketType;
use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        $isStandalone = Achievement::with('game')->find($this->input('ticketableId'))?->game->getIsStandalone() ?? false;
        $formOptionRule = $isStandalone ? 'nullable' : 'required';

        return [
            'ticketableModel' => 'required|string|in:achievement', // TODO or in:leaderboard
            'ticketableId' => 'required|integer|exists:achievements,id', // TODO could also be a leaderboard id
            'mode' => 'required|string|in:hardcore,casual',
            // TODO expand or compute this list via TicketType::appliesTo()
            'issue' => ['required', (new Enum(TicketType::class))->only([
                TicketType::TriggeredAtWrongTime,
                TicketType::DidNotTrigger,
            ])],
            'description' => 'required|string|max:2000',
            'emulator' => [$formOptionRule, 'string'],
            'emulatorVersion' => 'nullable|string',
            'core' => 'nullable|string',
            'gameHashId' => [$formOptionRule, 'integer'],
            'extra' => 'nullable|string',
        ];
    }
}
