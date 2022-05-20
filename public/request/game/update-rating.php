<?php

use App\Community\Enums\RatingType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'type' => ['required', Rule::in(RatingType::cases())],
    'rating' => 'required|integer|min:1|max:5',
]);

if (submitGameRating($user, $input['type'], $input['game'], $input['rating'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
