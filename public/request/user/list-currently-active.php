<?php

use App\Community\Services\ActivePlayersService;

$searchValue = request('search');
$fetchAll = request('all', false);
$targetGameIds = request('targetGameIds');

if ($targetGameIds) {
    $targetGameIds = explode(',', $targetGameIds);
}

$activePlayersService = new ActivePlayersService();
$loadedActivePlayers = $activePlayersService->loadActivePlayers($searchValue, $fetchAll, $targetGameIds);

return response()->json($loadedActivePlayers);
