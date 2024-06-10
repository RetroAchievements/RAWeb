<?php

use App\Models\Game;
use App\Models\GameHash;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game']);
name('game.hash');

render(function (View $view, Game $game) {
    $hashes = $game->hashes()->with('user')->orderBy('name')->orderBy('md5')->get();
    $numHashes = $hashes->count();

    $unlabeledHashes = $hashes->filter(function ($hash) {
        return empty($hash->name);
    });
    $labeledHashes = $hashes->reject(function ($hash) {
        return empty($hash->name);
    });

    return $view->with([
        'labeledHashes' => $labeledHashes,
        'numHashes' => $numHashes,
        'unlabeledHashes' => $unlabeledHashes,
    ]);
});

?>

@props([
    'labeledHashes' => null, // Collection<GameHash>
    'numHashes' => 0,
    'unlabeledHashes' => null, // Collection<GameHash>
])

<x-app-layout pageTitle="Supported Game Files - {{ $game->title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Supported Game Files"
    />

    <div class="mt-3 w-full flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Supported Game Files</h1>
    </div>

    @can('manage', App\Models\GameHash::class, ['game' => $game])
        <div class="mt-1 mb-6">
            <x-game.link-buttons.index
                :allowedLinks="['manage-hashes']"
                :game="$game"
                variant="row"
            />
        </div>
    @endcan

    <div class="bg-embed rounded p-4 mt-2 flex flex-col gap-y-4">
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
                <a href="{{ route('forum.topic', ['forumTopic' => $game->ForumTopicID]) }}">official forum topic</a>.
            @endif
        </p>
    </div>

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
