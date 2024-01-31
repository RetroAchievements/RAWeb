@props([
    'averageCompletionPercentage' => '0.0',
    'averageFinishedGames' => 0,
    'averagePointsPerWeek' => 0,
    'developerStats' => [],
    'hardcoreRankMeta' => [],
    'recentPointsEarned' => [],
    'socialStats' => [],
    'softcoreRankMeta' => [],
    'totalHardcoreAchievements' => 0,
    'totalSoftcoreAchievements' => 0,
    'userClaims' => null, // ?array
    'userMassData' => [],
    'username' => '',
])

<?php

use App\Enums\Permissions;

$registeredPermission = Permissions::Registered;
$jrDevPermission = Permissions::JuniorDeveloper;
?>

<div class="relative">
    <x-user.profile.primary-meta
        :hardcoreRankMeta="$hardcoreRankMeta"
        :softcoreRankMeta="$softcoreRankMeta"
        :userMassData="$userMassData"
        :username="$username"
    />

    @if ($userMassData['Permissions'] >= $registeredPermission)
        <div class="flex gap-x-1 items-center mb-2 top-0.5 right-0 sm:hidden md:flex md:flex-col md:items-end md:gap-y-1 md:absolute lg:hidden xl:flex xl:absolute">
            <x-user.profile.social-interactivity :username="$username"/>
            <x-user.profile.follows-you-label :username="$username"/>
        </div>
    @endif
</div>

<hr class="border-embed-highlight mb-2"/>

@if (!empty($userMassData['LastGame']))
    <x-user.profile.last-seen-in :userMassData="$userMassData"/>
@endif

<x-user.profile.player-stats
    :averageCompletionPercentage="$averageCompletionPercentage"
    :averageFinishedGames="$averageFinishedGames"
    :averagePointsPerWeek="$averagePointsPerWeek"
    :hardcoreRankMeta="$hardcoreRankMeta"
    :recentPointsEarned="$recentPointsEarned"
    :softcoreRankMeta="$softcoreRankMeta"
    :totalHardcoreAchievements="$totalHardcoreAchievements"
    :totalSoftcoreAchievements="$totalSoftcoreAchievements"
    :userMassData="$userMassData"
/>

@if (!empty($developerStats))
    <x-user.profile.developer-stats
        :developerStats="$developerStats"
        :userClaims="$userClaims"
        :userMassData="$userMassData"
        :username="$username"
    />
@endif

<x-user.profile.social-stats
    :socialStats="$socialStats"
    :username="$username"
/>
