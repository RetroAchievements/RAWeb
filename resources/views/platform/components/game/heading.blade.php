@props([
    'gameId' => 0,
    'gameTitle' => 'Unknown Game',
    'consoleId' => 0,
    'consoleName' => 'Unknown Console',
    'user' => null,
    'userPermissions' => null,
])

<?php

use App\Community\Enums\UserGameListType;
use App\Platform\Models\System;
use App\Enums\Permissions;

$type = UserGameListType::Play;
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

        @if (!empty($user) && $userPermissions >= Permissions::Registered && System::isGameSystem($consoleId))
            <x-game.add-to-list :gameId="$gameId" :type="$type" :user="$user" />
        @endif
    </div>
</h1>
