<?php

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Number;

function getDeleteDate(string|Carbon $deleteRequested): string
{
    if (empty($deleteRequested)) {
        return '';
    }

    if (!$deleteRequested instanceof Carbon) {
        $deleteRequested = Carbon::parse($deleteRequested);
    }

    return $deleteRequested->addDays(14)->format('Y-m-d');
}

function cancelDeleteRequest(string $username): bool
{
    $user = User::whereName($username)->first();
    if (!$user) {
        return false;
    }

    $user->update(['delete_requested_at' => null]);

    $previousCancelCount = Comment::withTrashed()
        ->accountDeletionForUser($user->id)
        ->where('body', 'like', '%canceled account deletion%')
        ->count();

    $commentBody = $user->display_name . ' canceled account deletion';
    if ($previousCancelCount > 0) {
        $commentBody .= ' (' . Number::ordinal($previousCancelCount + 1) . ' cancellation)';
    }

    addArticleComment('Server', CommentableType::UserModeration, $user->id, $commentBody);

    return true;
}
