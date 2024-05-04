<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:UserAccounts,User',
    'leaderboard' => 'required|integer|exists:LeaderboardDef,ID',
    'reason' => 'nullable|string|max:200',
]);

$leaderboardId = (int) $input['leaderboard'];
$targetUser = User::firstWhere('User', $input['user']);
$reason = $input['reason'];

if (!$targetUser) {
    return back()->withErrors(__('legacy.error.error'));
}

// Only let jr. devs remove their own entries
if ($permissions == Permissions::JuniorDeveloper && $user->id !== $targetUser->id) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (removeLeaderboardEntry($targetUser, $leaderboardId, $score)) {
    if ($targetUser->id !== $user->id) {
        $commentText = "{$user->display_name} removed {$targetUser->display_name}'s entry of {$score} from this leaderboard";
        if (!empty($reason)) {
            $commentText .= ". Reason: {$reason}";
        }
        addArticleComment("Server", ArticleType::Leaderboard, $leaderboardId, $commentText, $user->username);
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
