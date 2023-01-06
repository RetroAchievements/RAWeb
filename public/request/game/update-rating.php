<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\RatingType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'type' => ['required', Rule::in(RatingType::cases())],
    'rating' => 'required|integer|min:1|max:5',
]);

if (submitGameRating($user, $input['type'], $input['game'], $input['rating'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
