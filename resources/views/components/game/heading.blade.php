<?php

use App\Community\Enums\UserGameListType;
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
?>

<h1 class="text-h3">
    <span class="block mb-1">
        <x-game-title :rawTitle="$gameTitle" />
    </span>

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
