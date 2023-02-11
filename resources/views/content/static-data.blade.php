<?php

use LegacyApp\Site\Models\StaticData;

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

$lastRegisteredUserAtDate = new DateTime($lastRegisteredUserAt);
$now = new DateTime();
$interval = $lastRegisteredUserAtDate->diff($now);

$timeUnits = [
    "y" => "year",
    "m" => "month",
    "d" => "day",
    "h" => "hour",
    "i" => "minute",
    "s" => "second",
];
foreach ($timeUnits as $format => $unit) {
    if ($interval->$format >= 1) {
        $unit = $interval->$format > 1 ? $unit . "s" : $unit;
        // "%$format $unit ago" --> "5 minutes ago"
        $lastRegisteredUserTimeAgo = $interval->format("%$format $unit ago");
        break;
    }
}

if ($lastRegisteredUser == null) {
    $lastRegisteredUser = 'unknown';
}
?>
<div class="component statistics">
    <h3>Statistics</h3>

    <div class="infobox mb-4">
        <div class="w-full">
            <div class="w-full h-16 mb-2 flex flex-col justify-center items-center">
                <span class="text-2xl">{{ number_format($totalPointsEarned) }}</span>
                <span>Points earned since 2nd March 2013</span>
            </div>

            <div class="grid grid-cols-2 gap-px">
                <x-home-stats-embed label="Games" :count="$numGames" href="/gameList.php?s=1" />
                <x-home-stats-embed label="Achievements" :count="$numAchievements" href="/gameList.php?s=2" />
                <x-home-stats-embed label="Achievement Unlocks" :count="$numAwarded" href="/achievementList.php" />
                <x-home-stats-embed label="Registered Players" :count="$numRegisteredPlayers" href="/userList.php" />
            </div>
        </div>
    </div>

    <div>
        @if($staticData->lastRegisteredUser)
            The newest registered user is
            <x-user.avatar :user="$staticData->lastRegisteredUser" />, 
            who joined {{ $lastRegisteredUserTimeAgo }}.
        @endif
    </div>
</div>
