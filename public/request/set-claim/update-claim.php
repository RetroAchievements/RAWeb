<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'claim' => 'required|integer|exists:SetClaim,ID',
    'claim_special' => ['required', 'integer', Rule::in(ClaimSpecial::cases())],
    'claim_status' => ['required', 'integer', Rule::in(ClaimStatus::cases())],
    'claim_type' => ['required', 'integer', Rule::in(ClaimType::cases())],
    'claimed' => 'required|date',
    'claim_finish' => 'required|date',
    'comment' => 'required|string|max:2000',
    'set_type' => ['required', 'integer', Rule::in(ClaimSetType::cases())],
]);

if (updateClaim((int) $input['claim'], (int) $input['claim_type'], (int) $input['set_type'], (int) $input['claim_status'], (int) $input['claim_special'], $input['claimed'], $input['claim_finish'])) {
    addArticleComment("Server", ArticleType::SetClaim, (int) $input['game'], $input['comment']);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
