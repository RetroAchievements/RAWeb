<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimStatus;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'claim' => 'required|integer|exists:mysql_legacy.SetClaim,ID',
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
