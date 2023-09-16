<?php

use App\Community\Services\ActivePlayersService;

$searchValue = request('search');
$fetchAll = request('all', false);

$activePlayersService = new ActivePlayersService();
$loadedActivePlayers = $activePlayersService->loadActivePlayers($searchValue, $fetchAll);

return response()->json($loadedActivePlayers);
