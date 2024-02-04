@props([
    'hardcoreRankMeta' => [],
    'softcoreRankMeta' => [],
    'userMassData' => [],
    'username' => '',
])

<?php

use App\Enums\Permissions;
use Illuminate\Support\Carbon;

$me = Auth::user() ?? null;
$amIModerator = false;
if ($me) {
    $amIModerator = $me->getAttribute('Permissions') >= Permissions::Moderator;
}

$hasVisibleRole = (
    (
        $userMassData['Permissions'] !== Permissions::Registered
        && $userMassData['Permissions'] !== Permissions::Unregistered
    )
    || ($amIModerator && $userMassData['Permissions'] !== Permissions::Registered)
);

$roleLabel = $hasVisibleRole ? Permissions::toString($userMassData['Permissions']) : '';
$shouldMoveRoleToNextLine =
    $hasVisibleRole
    && ((mb_strlen($roleLabel) >= 12 && mb_strlen($username) >= 12) || mb_strlen($username) >= 16);
?>

<div class="flex border-x border-embed-highlight flex-row-reverse sm:flex-row gap-x-4 pb-5 bg-embed -mx-5 px-5 mt-[-15px] pt-5">
    <img
        src="{{ media_asset('/UserPic/' . $username . '.png') }}"
        alt="{{ $username }}'s avatar"
        class="rounded-sm h-[64px] w-[64px] sm:max-h-[128px] sm:max-w-[128px] sm:min-w-[128px] sm:min-h-[128px]"
    >

    <div class="w-full">
        <div class="flex sm:-mt-1 sm:flex-row sm:justify-start sm:items-center gap-x-2 {{ $hasVisibleRole ? 'mb-2 sm:mb-0' : '' }} {{ $shouldMoveRoleToNextLine ? 'flex-col' : 'items-center' }}">
            {{-- Username --}}
            <h1 class='border-0 text-lg sm:text-2xl font-semibold mb-0'>{{ $username }}</h1>

            {{-- Legacy Role --}}
            {{-- TODO: Support N roles. --}}
            @if ($hasVisibleRole)
                <div class="flex h-4 items-center justify-center bg-neutral-700 text-neutral-300 px-1.5 rounded sm:-mt-1">
                    <p class="text-2xs -mb-0.5">{{ $roleLabel }}</p>
                </div>
            @endif
        </div>

        {{-- Motto --}}
        @if (!empty($userMassData['Motto']))
            <div class="rounded bg-box-bg px-2 py-1 max-w-fit italic text-2xs hyphens-auto mb-3">
                <p style="word-break: break-word;">{{ $userMassData['Motto'] }}</p>
            </div>
        @endif

        {{-- 🚨 Space is limited. Do NOT display more than 4 rows of content in this div. --}}
        <div class="text-2xs">
            {{-- Points --}}
            <x-user.profile.points-display
                :hardcorePoints="$userMassData['TotalPoints']"
                :softcorePoints="$userMassData['TotalSoftcorePoints']"
                :weightedPoints="$userMassData['TotalTruePoints']"
            />

            {{-- Site Rank --}}
            <x-user.profile.site-rank-display
                :hardcoreRankMeta="$hardcoreRankMeta"
                :softcoreRankMeta="$softcoreRankMeta"
                :userMassData="$userMassData"
                :username="$username"
            />

            {{-- Last Activity --}}
            @if ($userMassData['LastActivity'])
                <p>
                    <span class="font-bold">Last Activity:</span>
                    <span class="cursor-help" title="{{ getNiceDate(strtotime($userMassData['LastActivity'])) }}">
                        {{ Carbon::parse($userMassData['LastActivity'])->diffForHumans() }}
                    </span>
                </p>
            @endif

            {{-- Member Since --}}
            <p>
                <span class="font-bold">Member Since:</span>
                <span>
                    {{ Carbon::parse($userMassData['MemberSince'])->format('d M Y') }}
                </span>
            </p>
        </div>

        <div class="hidden sm:flex sm:gap-x-2 sm:-ml-2 sm:mt-1 md:hidden lg:flex xl:hidden">
            <x-user.profile.social-interactivity :username="$username" />
            <x-user.profile.follows-you-label :username="$username" />
        </div>
    </div>
</div>
