@props([
    'developerStats' => [],
    'userClaims' => null, // ?array
    'userMassData' => [],
])

<?php
use App\Community\Enums\ClaimType;
use App\Community\Enums\ClaimSpecial;

$numAllowedClaims = permissionsToClaim($userMassData['Permissions']);

$primaryClaims = collect($userClaims)->filter(function ($entity) {
    return $entity['ClaimType'] !== ClaimType::Collaboration && $entity['Special'] === ClaimSpecial::None;
})->toArray();

$collabClaims = collect($userClaims)->filter(function ($entity) {
    return $entity['ClaimType'] === ClaimType::Collaboration;
})->toArray();

$specialClaims = collect($userClaims)->filter(function ($entity) {
    return $entity['Special'] !== ClaimSpecial::None;
})->toArray();
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
