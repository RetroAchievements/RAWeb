<?php

use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,game', 'can:view,user']);
name('game.compare-unlocks');

?>

@props([
    'user' => null,
    'otherUser' => null,
    'game' => null,
    'achievements' => [],
    'sortOrder' => 'display',
])

@php
use App\Enums\Permissions;

$availableSorts = [
    'selfUnlocks' => 'My Unlock Times',
    'otherUnlocks' => 'Their Unlock Times',
    'display' => 'Display Order',
    'title' => 'Achievement Title',
];

$userUnlockCount = 0;
$otherUserUnlockCount = 0;
$userUnlockHardcoreCount = 0;
$otherUserUnlockHardcoreCount = 0;
$numAchievements = count($achievements);


foreach ($achievements as $achievement) {
    if (array_key_exists('userTimestamp', $achievement)) {
        $userUnlockCount++;
        if ($achievement['userHardcore'] ?? false) {
            $userUnlockHardcoreCount++;
        }
    }

    if (array_key_exists('otherUserTimestamp', $achievement)) {
        $otherUserUnlockCount++;
        if ($achievement['otherUserHardcore'] ?? false) {
            $otherUserUnlockHardcoreCount++;
        }
    }
}

$canModerate = ($user->Permissions >= Permissions::Moderator);

@endphp

<x-app-layout
    pageTitle="Compare Unlocks - {{ $game->Title }}"
    pageDescription="Compares unlocks between {{ $user->User }} and {{ $otherUser->User }} for {{ $game->Title }}"
>
    <x-user.breadcrumbs
        :targetUsername="$otherUser->User"
        :parentPage="$game->Title"
        :parentPageUrl="$game->permalink"
        currentPage="Compare Unlocks"
    />

    <div class="mt-3 mb-3 w-full relative flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Compare Unlocks</h1>

        @if ($canModerate)
            <x-hidden-controls-toggle-button>Moderate</x-hidden-controls-toggle-button>
        @endif
    </div>

@if ($canModerate)
    <x-hidden-controls>
        <div class="grid md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <ul class="flex flex-col gap-2">
                <x-game.link-buttons.game-link-button
                    icon="🔬"
                    href="{{ route('user.game.activity', ['user' => $otherUser, 'game' => $game]) }}"
                >
                    View User Game Activity
                </x-game.link-buttons.game-link-button>
            </ul>
        </div>
    </x-hidden-controls>
@endif

@if ($numAchievements === 0)
    <p>This game has no published achievements.</p>
@else
    <x-main sidebarPosition="left">
        <x-slot name="sidebar">
            <x-game.compare-progress
                :game="$game"
                :user="$user"
            />
        </x-slot>

        <x-meta-panel
            :availableSorts="$availableSorts"
            :selectedSortOrder="$sortOrder"
        />

        <div class="overflow-x-auto lg:overflow-x-visible">
            <table class="table-highlight mb-4">
                <thead>
                    <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[1] bg-box">
                        <th style="width:40%">Achievement</th>
                        <th style="width:30%">
                        {!! userAvatar($otherUser->User, label: true, icon: true, iconSize: 24, iconClass: 'rounded-sm') !!}
                        </th>
                        <th style="width:30%">
                        {!! userAvatar($user->User, label: true, icon: true, iconSize: 24, iconClass: 'rounded-sm') !!}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($achievements as $achievement)
                        <tr>
                            <td>
                                <div>
                                    {!! achievementAvatar($achievement, label: true, icon: false) !!}
                                    <p>{{ $achievement['Description'] }}</p>
                                </div>
                            </td>
                            <td>
                                @if ($achievement['otherUserTimestamp'] ?? null)
                                    {!! achievementAvatar($achievement, label: false, icon: $achievement['BadgeName'], iconSize: 32, iconClass: ($achievement['otherUserHardcore'] ?? false) ? 'goldimage' : 'badgeimage') !!}
                                    <span class="smalldate whitespace-nowrap">{{ $achievement['otherUserTimestamp'] }}
                                        @if ($achievement['otherUserHardcore'] ?? false)
                                            <br>HARDCORE
                                        @endif
                                    </span>
                                @else
                                    {!! achievementAvatar($achievement, label: false, icon: $achievement['BadgeName'] . '_lock', iconSize: 32) !!}
                                @endif
                            </td>
                            <td>
                                @if ($achievement['userTimestamp'] ?? null)
                                    {!! achievementAvatar($achievement, label: false, icon: $achievement['BadgeName'], iconSize: 32, iconClass: ($achievement['userHardcore'] ?? false) ? 'goldimage' : 'badgeimage') !!}
                                    <span class="smalldate whitespace-nowrap">{{ $achievement['userTimestamp'] }}
                                        @if ($achievement['userHardcore'] ?? false)
                                            <br>HARDCORE
                                        @endif
                                    </span>
                                @else
                                    {!! achievementAvatar($achievement, label: false, icon: $achievement['BadgeName'] . '_lock', iconSize: 32) !!}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td>
                            {{ $otherUserUnlockCount }} of {{ $numAchievements }} unlocked
                            @if ($otherUserUnlockHardcoreCount === 0 && $otherUserUnlockCount > 0)
                                (softcore only)
                            @elseif ($otherUserUnlockCount > $otherUserUnlockHardcoreCount)
                                ({{ $otherUserUnlockCount - $otherUserUnlockHardcoreCount }} softcore)
                            @endif
                        </td>
                        <td>
                            {{ $userUnlockCount }} of {{ $numAchievements }} unlocked
                            @if ($userUnlockHardcoreCount === 0 && $userUnlockCount > 0)
                                (softcore only)
                            @elseif ($userUnlockCount > $userUnlockHardcoreCount)
                                ({{ $userUnlockCount - $userUnlockHardcoreCount }} softcore)
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-main>
@endif
</x-app-layout>