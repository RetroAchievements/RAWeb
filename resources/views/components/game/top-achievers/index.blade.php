@props([
    'game' => null, // Game
])

<?php

use App\Platform\Enums\AchievementSetType;
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
    <div class="text-right mb-4">
        <a class="btn btn-link" href="{{ route('game.top-achievers.index', ['game' => $game]) }}">more...</a>
    </div>
@endif

@if (app()->environment('local'))
    <?php
        $achievementSet = $game->gameAchievementSets()->where('type', AchievementSetType::Core)->first()?->achievementSet;
    ?>
    <div class="mb-4">
        <table class="table-highlight">
            <tbody>
                <x-game.top-achievers.time-to label='Median time to beat' :value="$game->median_time_to_beat" :count="$game->times_beaten" />
                <x-game.top-achievers.time-to label='Median time to beat (hardcore)' :value="$game->median_time_to_beat_hardcore" :count="$game->times_beaten_hardcore" />
                <x-game.top-achievers.time-to label='Median time to complete' :value="$achievementSet->median_time_to_complete" :count="$achievementSet->times_completed" />
                <x-game.top-achievers.time-to label='Median time to master' :value="$achievementSet->median_time_to_complete_hardcore" :count="$achievementSet->times_completed_hardcore" />
            </tbody>
        </table>
    </div>
@endif