<?php

use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'body' => [
        'required',
        'string',
        'max:2000',
        new ContainsRegularCharacter(),
    ],
    'commentable_id' => 'required|integer',
    'commentable_type' => 'required|integer',
]);

$commentableId = (int) $input['commentable_id'];
$commentableTypeInt = (int) $input['commentable_type'];

$commentableType = CommentableType::fromLegacyInteger($commentableTypeInt);
if ($commentableType === null) {
    return back()->withErrors(__('legacy.error.error'));
}

$commentable = match ($commentableType) {
    CommentableType::User, CommentableType::UserModeration => User::find($commentableId),
    default => null,
};

$userModel = User::find($userDetails['id']);
if (!$userModel->can('create', [Comment::class, $commentable, $commentableType])) {
    return back()->withErrors(__('legacy.error.error'));
}

if (addArticleComment($userModel->username, $commentableType, $commentableId, $input['body'])) {
    return back()->with('success', __('legacy.success.send'));
}

return back()->withErrors(__('legacy.error.error'));
