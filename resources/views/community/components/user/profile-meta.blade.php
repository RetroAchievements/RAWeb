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
use App\Site\Enums\Permissions;

$jrDevPermission = Permissions::JuniorDeveloper;
?>

<div class="relative">
    <x-user.profile.primary-meta
        :hardcoreRankMeta="$hardcoreRankMeta"
        :softcoreRankMeta="$softcoreRankMeta"
        :userMassData="$userMassData"
        :username="$username"
    />

    <div class="mb-2 sm:ml-[142px] md:ml-0 md:absolute md:top-0.5 md:right-0 lg:relative lg:ml-[142px] xl:absolute xl:ml-0">
        <x-user.profile.social-interactivity :username="$username" />
    </div>
</div>

<hr class="border-embed-highlight mb-2" />

<x-user.profile.follows-you-label :username="$username" />

@if (!empty($userMassData['LastGame']))
    <x-user.profile.last-seen-in :userMassData="$userMassData" />
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

@if ($userMassData['ContribCount'] > 0 || $userMassData['Permissions'] >= $jrDevPermission)
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
