<?php

use App\Enums\Permissions;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Actions\RemoveLeaderboardEntry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$currentUser = User::find($userDetails['ID']);

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

$entry = LeaderboardEntry::where('leaderboard_id', $leaderboardId)
    ->where('user_id', $targetUser->id)
    ->first();

if (!$currentUser->can('delete', $entry)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

(new RemoveLeaderboardEntry())->execute($entry, $reason);

return back()->with('success', __('legacy.success.ok'));
