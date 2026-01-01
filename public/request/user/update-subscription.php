<?php

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Enums\Permissions;
use App\Models\User;
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

$service = new SubscriptionService();
$service->updateSubscription(
    User::find($userDetails['id']),
    SubscriptionSubjectType::from($subjectType),
    $subjectID,
    $input['operation'] === "subscribe");

return back()->with('success', __('legacy.success.' . $input['operation']));
