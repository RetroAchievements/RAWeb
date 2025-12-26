<?php

use App\Community\Enums\ClaimType;
use App\Community\Enums\ClaimSpecial;
use App\Models\AchievementSetClaim;

?>

@props([
    'developerStats' => [],
    'user' => null, // ?User
    'userClaims' => null, // ?array
    'userMassData' => [],
])

<?php
$numAllowedClaims = AchievementSetClaim::getMaxClaimsForUser($user);

$primaryClaims = [];
$specialClaims = [];
$collabClaims = [];

if (!empty($userClaims)) {
    foreach ($userClaims as $claim) {
        if ($claim['ClaimType'] === ClaimType::Collaboration->value) {
            $collabClaims[] = $claim;
        } elseif ($claim['Special'] !== ClaimSpecial::None->value) {
            $specialClaims[] = $claim;
        } else {
            $primaryClaims[] = $claim;
        }
    }
}
?>

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Developer Stats</p>
<div class="relative w-full p-2 mb-6 bg-embed rounded">
    <x-user.profile.arranged-stat-items
        :stats="[
            $developerStats['setsWorkedOnStat'],                    $developerStats['codeNotesCreatedStat'],
            $developerStats['achievementsUnlockedByPlayersStat'],   $developerStats['leaderboardsCreatedStat'],
            $developerStats['pointsAwardedToPlayersStat'],          $developerStats['openTicketsStat']
        ]"
    />

    @if (!empty($userClaims))
        <div class="mt-4 flex flex-col gap-y-2">
            @if (!empty($primaryClaims))
                <x-user.profile.developer-claims-list
                    label="Primary Claims"
                    :claims="$primaryClaims"
                    :numAllowedClaims="$numAllowedClaims"
                />
            @endif

            @if (!empty($specialClaims))
                <x-user.profile.developer-claims-list
                    label="Special Claims"
                    :claims="$specialClaims"
                />
            @endif

            @if (!empty($collabClaims))
                <x-user.profile.developer-claims-list
                    label="Collaboration Claims"
                    :claims="$collabClaims"
                />
            @endif
        </div>
    @endif
</div>
