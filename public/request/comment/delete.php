<?php

use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'comment' => 'required|integer|exists:Comment,ID',
]);

$comment = Comment::findOrFail((int) $input['comment']);
$user = User::find($userDetails['ID']);

if (removeComment($comment, $user)) {
    return response()->json(['message' => __('legacy.success.delete')]);
}

abort(400);
