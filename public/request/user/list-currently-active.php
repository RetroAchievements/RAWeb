<?php

use App\Community\Services\ActivePlayersService;

$searchValue = request('search');
$fetchAll = request('all', false);
$targetGameIds = request('targetGameIds');

$activePlayersService = new ActivePlayersService();
$loadedActivePlayers = $activePlayersService->loadActivePlayers($searchValue, $fetchAll, $targetGameIds);

return response()->json($loadedActivePlayers);
