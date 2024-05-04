<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Comment;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

Comment::where('ArticleType', ArticleType::User)
    ->where('ArticleID', $user->id)
    ->forceDelete(); // TODO the legacy query did a hard delete - let's convert this to use soft deletes!!

return back()->with('success', __('legacy.success.delete'));
