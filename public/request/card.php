<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (request()->isMethod('GET') && request()->header('X-Requested-With') !== 'XMLHttpRequest') {
    abort(405);
}

$input = Validator::validate(Arr::wrap(request()->all()), [
    'type' => ['required', 'string', Rule::in(['user', 'achievement', 'game', 'hub', 'ticket'])],
    'id' => 'required',
    'context' => 'sometimes|nullable|string',
]);

$type = $input['type'];
$context = $input['context'] ?? null;

$html = match ($type) {
    'achievement' => renderAchievementCard($input['id'], $context),
    'game' => renderGameCard((int) $input['id'], $context),
    'hub' => renderHubCard((int) $input['id']),
    'ticket' => renderTicketCard((int) $input['id']),
    'user' => renderUserCard($input['id']),
    default => '?',
};

// User cards and cards with context contain user-specific data and should not be CDN-cached.
// Other card types contain static content that can be cached at the CDN level.
$isUserSpecific = $type === 'user' || $context !== null;
$isCacheable = request()->isMethod('GET') && !$isUserSpecific;

$cacheControl = $isCacheable
    ? 'public, max-age=300' // 5 minutes
    : 'private, no-cache';

return response()
    ->json(['html' => $html])
    ->header('Cache-Control', $cacheControl);
