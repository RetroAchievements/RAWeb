@props([
    'developerStats' => [],
    'userClaims' => null, // ?array
    'userMassData' => [],
    'username' => '',
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

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Developer stats</p>
<div class="relative w-full p-2 mb-6 bg-embed rounded">
    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1 {{ !empty($userClaims) ? 'mb-4' : '' }}">
        <div class="flex flex-col gap-y-1">
            <x-user.profile.stat-element label="Achievement sets worked on">
                @if ($developerStats['gameAuthoredAchievementsCount'] > 0)
                    <a href="{{ route('developer.sets', $username) }}" class="font-bold">
                        {{ localized_number($developerStats['gameAuthoredAchievementsCount']) }}
                    </a>
                @else
                    <span class="italic text-muted">0</span>
                @endif
            </x-user.profile.stat-element>

            <x-user.profile.stat-element label="Achievements unlocked by players">
                @if ($userMassData['ContribCount'] > 0)
                    <a href="{{ route('developer.feed', $username) }}" class="font-bold">
                        {{ localized_number($userMassData['ContribCount']) }}
                    </a>
                @else
                    <span class="italic text-muted">0</span>
                @endif
            </x-user.profile.stat-element>

            <x-user.profile.stat-element label="Points awarded to players">
                @if ($userMassData['ContribYield'] > 0)
                    <a href="{{ route('developer.feed', $username) }}" class="font-bold">
                        {{ localized_number($userMassData['ContribYield']) }}
                    </a>
                @else
                    <span class="italic text-muted">0</span>
                @endif
            </x-user.profile.stat-element>
        </div>

        <div class="flex flex-col gap-y-1">
            <x-user.profile.stat-element label="Code notes created">
                @if ($developerStats['totalAuthoredCodeNotes'] > 0)
                    <a href="{{ '/individualdevstats.php?u=' . $username . '#code-notes' }}" class="font-bold">
                        {{ localized_number($developerStats['totalAuthoredCodeNotes']) }}
                    </a>
                @else
                    <span class="italic text-muted">0</span>
                @endif
            </x-user.profile.stat-element>
    
            <x-user.profile.stat-element label="Leaderboards created">
                @if ($developerStats['totalAuthoredLeaderboards'] > 0)
                    <a href="{{ '/individualdevstats.php?u=' . $username }}" class="font-bold">
                        {{ localized_number($developerStats['totalAuthoredLeaderboards']) }}
                    </a>
                @else
                    <span class="italic text-muted">0</span>
                @endif
            </x-user.profile.stat-element>
    
            <x-user.profile.stat-element label="Open tickets">
                @if ($developerStats['openTickets'] === null)
                    <span class="italic" title="Tickets can't be assigned to {{ $username }}">n/a</span>
                @elseif ($developerStats['openTickets'] === 0)
                    <span class="italic text-muted">0</span>
                @else
                    <a href="{{ '/ticketmanager.php?u=' . $username }}" class="font-bold">
                        {{ $developerStats['openTickets'] }}
                    </a>
                @endif
            </x-user.profile.stat-element>
        </div>
    </div>

    <div class="flex flex-col gap-y-2">
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
</div>
