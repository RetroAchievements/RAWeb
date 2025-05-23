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
    ->pluck('title')
    ->map(function($title) {
        $tagIndex = strrpos($title, '~');
        return $tagIndex ? trim(substr($title, $tagIndex + 1)) : $title;
    })
    ->filter(function($title) use ($gameTitle) {
        return $title !== $gameTitle && !empty($title);
    })
    ->unique()
    ->values()
    ->toArray();
$alternateTitles = match(count($alternateReleases)) {
    0 => '',
    1 => $alternateReleases[0],
    2 => $alternateReleases[0] . ' and ' . $alternateReleases[1],
    default => implode(', ', array_slice($alternateReleases, 0, -1)) . ', and ' . end($alternateReleases)
};
?>

<h1 class="text-h3">
    <span class="block mb-1">
        <x-game-title :rawTitle="$gameTitle" />
    </span>

    @if ($alternateTitles)
        <span class="block mb-2 mt-1 smalltext">Also known as {{ $alternateTitles }}</span>
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
