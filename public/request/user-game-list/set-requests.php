<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'user' => 'required|string|exists:UserAccounts,display_name',
]);

$gameId = (int) $input['game'];
$user = $input['user'];

$userModel = User::whereName($user)->first();

$totalRequests = getUserRequestsInformation($userModel, $gameId);
$totalRequests['gameRequests'] = getSetRequestCount($gameId);

return response()->json($totalRequests);
