<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'type' => ['required', 'string', Rule::in(['user', 'achievement', 'game', 'ticket'])],
    'id' => 'required',
    'context' => 'sometimes|nullable|string',
]);

return response()->json([
    'html' => match ($input['type']) {
        'achievement' => renderAchievementCard($input['id'], context: $input['context']),
        'game' => renderGameCard((int) $input['id'], targetUsername: $input['context']),
        'ticket' => renderTicketCard((int) $input['id']),
        'user' => renderUserCard($input['id']),
        default => '?',
    },
]);
