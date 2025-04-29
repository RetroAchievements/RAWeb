<?php

use App\Connect\Actions\SubmitCodeNoteAction;
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

$action = new SubmitCodeNoteAction();
$result = $action->execute(
    $input['gameId'],
    $input['address'],
    $input['note'] ?? '',
    User::whereName($user)->first()
);

if (!$result['Success']) {
    abort($result['Status'] ?? 400);
}

return response()->json(['message' => __('legacy.success.ok')]);
