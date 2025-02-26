<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'type' => ['required', 'string', Rule::in(['user', 'achievement', 'game', 'hub', 'ticket'])],
    'id' => 'required',
    'context' => 'sometimes|nullable|string',
]);

$context = $input['context'] ?? null;

return response()->json([
    'html' => match ($input['type']) {
        'achievement' => renderAchievementCard($input['id'], $context),
        'game' => renderGameCard((int) $input['id'], $context),
        'hub' => renderHubCard((int) $input['id']),
        'ticket' => renderTicketCard((int) $input['id']),
        'user' => renderUserCard($input['id']),
        default => '?',
    },
]);
