<?php

use App\Enums\Permissions;
use App\Models\Game;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$gameID = (int) request()->query('g');
if (empty($gameID)) {
    abort(404);
}

$game = Game::with('system')->find($gameID);
if (!$game) {
    abort(404);
}

$hashes = $game->hashes()->with('user')->orderBy('name')->orderBy('md5')->get();

$numHashes = $hashes->count();

$unlabeledHashes = $hashes->filter(function ($hash) {
    return empty($hash->name);
});
$labeledHashes = $hashes->reject(function ($hash) {
    return empty($hash->name);
});
?>

<x-app-layout pageTitle="Supported Game Files - {{ $game->title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Supported Game Files"
    />

    <div class="mt-3 -mb-3 w-full flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Supported Game Files</h1>
    </div>

    <div class="bg-embed rounded p-4 mt-8 flex flex-col gap-y-4">
        <p>
            <span class="font-bold">
                RetroAchievements requires that the game files you use with your emulator
                are the same as, or compatible with, those used to create the game's achievements.
            </span>
            This page shows you what ROM hashes are compatible with the game's achievements.
        </p>

        <p>
            Details on how the hash is generated for each system can be found
            <a href='https://docs.retroachievements.org/Game-Identification/'>here</a>.

            @if ($game->ForumTopicID > 0)
                Additional information for these hashes may be listed on the
                <a href="{{ 'viewtopic.php?t=' . $game->ForumTopicID }}">official forum topic</a>.
            @endif
        </p>
    </div>

    <?php
    echo "<div class='mt-4 mb-1'>";
    if ($permissions >= Permissions::Developer) {
        echo "<div class='devbox mb-3'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
        echo "<div id='devboxcontent' style='display: none'>";
        echo "<a href='/game/$gameID/hashes/manage'>Manage Hashes</a>";
        echo "</div>";
        echo "</div>";
    }
    ?>

    <p class="mt-4 mb-1">
        There {{ $numHashes === 1 ? 'is' : 'are' }} currently <span class="font-bold">{{ $numHashes }}</span>
        supported game file {{ strtolower(__res('game-hash', $numHashes)) }} registered for this game.
    </p>

    <div class="bg-embed p-4 rounded">
        <ul class="flex flex-col gap-y-3">
            @foreach ($labeledHashes as $hash)
                <x-supported-game-files.hash-listing :hash="$hash" />
            @endforeach
        </ul>

        @if (!$labeledHashes->isEmpty() && !$unlabeledHashes->isEmpty())
            <div class="my-6"></div>
        @endif
        
        @if (!$unlabeledHashes->isEmpty())
            <p class="font-bold">Unlabeled Game File Hashes</p>

            <ul class="flex flex-col gap-y-0">
                @foreach ($unlabeledHashes as $hash)
                        <x-supported-game-files.hash-listing :hash="$hash" />
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
