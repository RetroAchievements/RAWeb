@props([
    'game' => null, // Game
])

<?php

use App\Platform\Services\GameTopAchieversService;

$service = new GameTopAchieversService();
$service->initialize($game);
[$numMasters, $topAchievers] = $service->getTopAchieversComponentData();

?>

@if ($numMasters >= 10)
    <x-game.top-achievers.latest-masters :latestMasters="$topAchievers" :numMasters="$numMasters" />
@elseif ($game->points_total === 0)
    <x-game.top-achievers.most-points :highestPointEarners="$topAchievers" :maxScore="$game->achievements_published" isEvent="true" />
@else
    <x-game.top-achievers.most-points :highestPointEarners="$topAchievers" :maxScore="$game->points_total" />
@endif

@if ($game->players_hardcore > 10)
    <div class="text-right">
        <a class="btn btn-link" href="{{ route('game.top-achievers.index', ['game' => $game]) }}">more...</a>
    </div>
@endif
