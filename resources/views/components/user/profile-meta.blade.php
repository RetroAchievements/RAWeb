<?php

use App\Enums\Permissions;
?>

@props([
    'developerStats' => [],
    'hardcoreRankMeta' => [],
    'playerStats' => [],
    'socialStats' => [],
    'softcoreRankMeta' => [],
    'userClaims' => null, // ?array
    'userMassData' => [],
    'username' => '',
])

<?php
$registeredPermission = Permissions::Registered;
$jrDevPermission = Permissions::JuniorDeveloper;

$isUserStatsDefaultExpanded = request()->cookie('prefers_hidden_user_profile_stats') !== 'true';
?>

<div class="relative mb-2">
    <x-user.profile.primary-meta
        :hardcoreRankMeta="$hardcoreRankMeta"
        :softcoreRankMeta="$softcoreRankMeta"
        :userMassData="$userMassData"
        :username="$username"
    />

    @if ($userMassData['Permissions'] >= $registeredPermission)
        <div class="flex gap-x-1 items-center mt-2 mb-2 top-5 right-0 sm:hidden md:mt-0 md:flex md:flex-col md:items-end md:gap-y-1 md:absolute lg:hidden xl:flex xl:absolute">
            <x-user.profile.social-interactivity :username="$username" />
            <x-user.profile.follows-you-label :username="$username" />
        </div>
    @endif
</div>

@if (!empty($userMassData['LastGame']))
    <x-user.profile.last-seen-in :userMassData="$userMassData" />
@endif

<div
    x-data="{
        isExpanded: {{ $isUserStatsDefaultExpanded ? 'true' : 'false' }},
        handleToggle() {
            const newValue = !this.isExpanded;

            this.isExpanded = newValue;
            window.setCookie('prefers_hidden_user_profile_stats', String(!newValue));
        }
    }"
>
    <div>
        <div class="flex w-full justify-between items-center">
            <h2 class="text-h4 !mb-0">User Stats</h2>

            <button @click="handleToggle" class="btn transition-transform lg:active:scale-95 duration-75">
                <div
                    class="transition-transform @if ($isUserStatsDefaultExpanded) rotate-180 @endif"
                    :class="{ 'rotate-180': isExpanded }"
                >
                    <x-fas-chevron-down />
                </div>
            </button>
        </div>
    </div>

    <div
        @if (!$isUserStatsDefaultExpanded) x-cloak @endif
        x-show="isExpanded"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0 max-h-0 -translate-y-1.5 overflow-hidden"
        x-transition:enter-end="opacity-1 max-h-[1000px] translate-y-0 overflow-hidden"
        x-transition:leave="ease-in-out duration-200"
        x-transition:leave-start="opacity-1 max-h-[1000px] overflow-hidden"
        x-transition:leave-end="opacity-0 max-h-0 overflow-hidden"
        class="transition-all"
    >
        <div class="pt-3">
            <x-user.profile.player-stats
                :playerStats="$playerStats"
                :userMassData="$userMassData"
            />

            @if (!empty($developerStats))
                <x-user.profile.developer-stats
                    :developerStats="$developerStats"
                    :userClaims="$userClaims"
                    :userMassData="$userMassData"
                />
            @endif

            <x-user.profile.social-stats :socialStats="$socialStats" />
        </div>
    </div>
</div>
