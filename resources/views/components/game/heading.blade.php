<?php

use App\Community\Enums\UserGameListType;
use App\Models\GameRelease;
use App\Models\System;
use App\Enums\Permissions;
?>

{{-- TODO accept a Game model as a prop --}}
@props([
    'gameId' => 0,
    'gameTitle' => 'Unknown Game',
    'consoleId' => 0,
    'consoleName' => 'Unknown Console',
    'includeAddToListButton' => false,
])

<?php
$addToListType = UserGameListType::Play;
$iconUrl = getSystemIconUrl($consoleId);

$alternateReleases = GameRelease::query()
    ->where('game_id', $gameId)
    ->where('is_canonical_game_title', false)
    ->orderBy('title')
    ->select('title')
    ->pluck('title')
    ->toArray();
$alternateTitles = implode(', ', $alternateReleases);
?>

<h1 class="text-h3">
    <span class="block mb-1">
        <x-game-title :rawTitle="$gameTitle" />
    </span>

    @if ($alternateTitles)
    <span class="block mb-1 smalltext">Also known as {{ $alternateTitles }}</span>
    @endif

    <div class="flex justify-between">
        <div class="flex items-center gap-x-1">
            <img src="{{ $iconUrl }}" width="24" height="24" alt="Console icon">
            <span class="block text-sm tracking-tighter">{{ $consoleName }}</span>
        </div>

        {{-- TODO extract from the heading component --}}
        {{-- TODO use a policy --}}
        @php
            $user = $includeAddToListButton ? auth()->user() : null;
        @endphp
        @if ($user?->getAttribute('Permissions') >= Permissions::Registered && System::isGameSystem($consoleId))
            <livewire:game.add-to-list-button
                :$gameId
                label="Want to Play"
            />
        @endif
    </div>
</h1>
