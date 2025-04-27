<?php

use App\Connect\Commands\SubmitCodeNote;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'gameId' => 'required|integer',
    'address' => 'required|integer',
    'note' => 'nullable|string',
]);

$command = new SubmitCodeNote();
$command->gameId = $input['gameId'];
$command->address = $input['address'];
$command->note = $input['note'] ?? '';
$command->user = User::whereName($user)->first();

$result = $command->process();

if (!$result['Success']) {
    abort($result['Status'] ?? 400);
}

return response()->json(['message' => __('legacy.success.ok')]);
