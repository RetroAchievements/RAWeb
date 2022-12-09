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

$niceRegisteredAt = date("d M\nH:i", strtotime($lastRegisteredUserAt));

if ($lastRegisteredUser == null) {
    $lastRegisteredUser = 'unknown';
}
?>
<div class="component statistics">
    <h3>Statistics</h3>
    <div class="infobox">
        There are
        <a title="Achievement List" href="/gameList.php?s=2">{{ $numAchievements }}</a>
        achievements registered for
        <a title="Game List" href="/gameList.php?s=1">{{ $numGames }}</a> games.
        <a title="Achievement List" href="/achievementList.php">{{ $numAwarded }}</a>
        achievements have been awarded to the
        <a title="User List" href="/userList.php">{{ $numRegisteredPlayers }}</a>
        registered players (average: {{ $avAwardedPerPlayer }} per player)<br>
        <br>
        Since 2nd March 2013, a total of
        <span title="Awesome!"><strong>{{ $totalPointsEarned }}</strong></span>
        points have been earned by users on RetroAchievements.org.<br>
        <br>
        @if($staticData->lastRegisteredUser)
            The last registered user was
            <x-user.avatar :user="$staticData->lastRegisteredUser"/>
            on {{ $niceRegisteredAt }}.
        @endif
    </div>
</div>
