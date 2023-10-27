<?php

use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer',
]);

$achievementsUnlocked = User::firstWhere('User', $user)
    ->achievements()->where('GameID', $input['game'])
    ->withPivot(['unlocked_at', 'unlocked_hardcore_at'])
    ->orderBy('Title')
    ->get();

$dataOut = [];
foreach ($achievementsUnlocked as $achievementUnlocked) {
    $dataOut[] = [
        'ID' => $achievementUnlocked->ID,
        'Title' => $achievementUnlocked->Title,
        'Points' => $achievementUnlocked->Points,
        'HardcoreMode' => $achievementUnlocked->pivot->unlocked_hardcore_at ? 1 : 0,
    ];
}

return response()->json($dataOut);
