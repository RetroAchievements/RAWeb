<?php

use App\Community\Controllers\UserMessageChainController;
use App\Community\Models\UserMessage;
use App\Community\Models\UserMessageChain;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetail) || $permissions < Permissions::Registered) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
    'chain' => 'nullable|integer',
    'body' => 'required|string',
    'title' => 'required_without:chain',
    'recipient' => 'required_without:chain|exists:UserAccounts,User',
]);

if (array_key_exists('chain', $input) && $input['chain'] != null) {
    $userMessageChain = UserMessageChain::firstWhere('id', $input['chain']);
    if (!$userMessageChain) {
        return back()->withErrors(__('legacy.error.error'));
    }
    if ($userMessageChain->recipient_id != $user->ID && $userMessageChain->sender_id != $user->ID) {
        return back()->withErrors(__('legacy.error.error'));
    }
    UserMessageChainController::addToChain($userMessageChain, $user, $input['body']);
} else {
    $recipient = User::firstWhere('User', $input['recipient']);
    UserMessageChainController::newChain($user, $recipient, $input['title'], $input['body']);
}

return redirect(route("message.view-chain", $userMessageChain->id));
