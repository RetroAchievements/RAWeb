<?php

use App\Site\Models\StaticData;
use Illuminate\Support\Carbon;

/** @var ?StaticData $staticData */
$staticData = StaticData::with('lastRegisteredUser')
    ->first();

if ($staticData === null) {
    return;
}

$numGames = $staticData['NumGames'];
$numAchievements = $staticData['NumAchievements'];
$numAwarded = $staticData['NumAwarded'];
$numRegisteredPlayers = $staticData['NumRegisteredUsers'];

$avAwardedPerPlayer = 0;
if ($numRegisteredPlayers > 0) {
    $avAwardedPerPlayer = sprintf("%1.2f", $numAwarded / $numRegisteredPlayers);
}

$lastRegisteredUser = $staticData['LastRegisteredUser'];
$lastRegisteredUserAt = $staticData['LastRegisteredUserAt'];
$totalPointsEarned = $staticData['TotalPointsEarned'];

if ($lastRegisteredUser == null) {
    $lastRegisteredUser = 'unknown';
}

if ($lastRegisteredUserAt) {
    $lastRegisteredUserTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $lastRegisteredUserAt)->diffForHumans();
} else {
    $lastRegisteredUserTimeAgo = null;
}
?>
<div class="component statistics !mb-0">
    <h3>Statistics</h3>

    <div class="infobox">
        <div class="w-full">
            <div class="grid grid-cols-2 gap-px mb-2">
                <x-home-stats-embed label="Games" :count="$numGames" href="/gameList.php?s=1"/>
                <x-home-stats-embed label="Achievements" :count="$numAchievements" href="/achievementList.php"/>
                <x-home-stats-embed label="Registered Players" :count="$numRegisteredPlayers" href="/userList.php"/>
                <x-home-stats-embed label="Achievement Unlocks" :count="$numAwarded" href="/recentMastery.php"/>
            </div>

            <div class="w-full h-16 flex flex-col justify-center items-center">
                <span>Points earned since 2nd March 2013</span>
                <span class="text-2xl">{{ number_format($totalPointsEarned) }}</span>
            </div>
        </div>
    </div>

    <div>
        @if($staticData->lastRegisteredUser)
            <hr class="mt-4 mb-5 border-embed-highlight">

            <div class="w-full flex flex-col justify-center items-center">
                <p>Newest user</p>

                <div>
                    <x-user.avatar :user="$staticData->lastRegisteredUser"/>
                    @if($lastRegisteredUserTimeAgo)
                        <span class="text-2xs">({{ $lastRegisteredUserTimeAgo }})</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
