<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'user' => 'required|string|exists:UserAccounts,User',
]);

$gameID = (int) $input['game'];
$user = $input['user'];

$userModel = User::firstWhere('User', $user);

$totalRequests = getUserRequestsInformation($userModel, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

return response()->json($totalRequests);
