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
    @if ($numMasters > 10)
        <div class="text-right">
            <a class="btn btn-link" href="{{ route('game.masters', ['game' => $game]) }}">more...</a>
        </div>
    @endif
@elseif ($game->points_total === 0)
    <x-game.top-achievers.most-points :highestPointEarners="$topAchievers" isEvent="true" />
@else
    <x-game.top-achievers.most-points :highestPointEarners="$topAchievers" />
@endif
