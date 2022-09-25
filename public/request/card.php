<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$input = Validator::validate(request()->post(), [
    'type' => ['required', 'string', Rule::in(['user', 'achievement', 'game', 'ticket'])],
    'id' => 'required',
]);

return response()->json([
    'html' => match ($input['type']) {
        'achievement' => renderAchievementCard($input['id']),
        'game' => renderGameCard($input['id']),
        'ticket' => renderTicketCard($input['id']),
        'user' => renderUserCard($input['id']),
        default => '?',
    },
]);
