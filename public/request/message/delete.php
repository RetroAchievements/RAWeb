<?php

use App\Community\Controllers\UserMessageChainController;
use App\Community\Models\UserMessageChain;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'chain' => 'required|integer|exists:user_message_chains,id',
]);

/** @var User $user */
$user = request()->user();

$userMessageChain = UserMessageChain::firstWhere('id', $input['chain']);
if (!$userMessageChain) {
    return back()->withErrors(__('legacy.error.error'));
}
if ($userMessageChain->recipient_id != $user->ID && $userMessageChain->sender_id != $user->ID) {
    return back()->withErrors(__('legacy.error.error'));
}

UserMessageChainController::deleteChain($userMessageChain, $user);

response()->json(['message' => __('legacy.success.ok')]);
