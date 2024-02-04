<?php

use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'subject_type' => 'required|string',
    'subject_id' => 'required|integer',
    'operation' => 'required|string|in:subscribe,unsubscribe',
]);

$subjectType = $input['subject_type'];
$subjectID = $input['subject_id'];

$requiredPermissions = match ($subjectType) {
    SubscriptionSubjectType::GameTickets, SubscriptionSubjectType::GameAchievements => Permissions::JuniorDeveloper,
    default => Permissions::Registered,
};

if (!authenticateFromCookie($user, $permissions, $userDetails, $requiredPermissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!updateSubscription($subjectType, $subjectID, $userDetails['ID'], $input['operation'] === "subscribe")) {
    return back()->withErrors(__('legacy.error.subscription_update'));
}

return back()->with('success', __('legacy.success.' . $input['operation']));
