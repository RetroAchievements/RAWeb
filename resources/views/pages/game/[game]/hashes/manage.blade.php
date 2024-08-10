<?php

// TODO migrate to a filament management panel

use App\Community\Enums\ArticleType;
use App\Models\Game;
use App\Models\GameHash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game', 'can:manage,' . GameHash::class]);
name('game.hash.manage');

render(function (View $view, Game $game) {
    // This would all be in a dedicated service if we weren't planning on
    // migrating this page to a Filament panel.
    
    $gameWithSortedHashes = $game->load([
        'hashes' => function ($query) {
            $query->orderBy('Name');
        },
        'hashes.user'
    ]);

    $user = Auth::user();

    $articleTypeGameHash = ArticleType::GameHash;

    return $view->with([
        'articleTypeGameHash' => $articleTypeGameHash,
        'gameWithSortedHashes' => $gameWithSortedHashes,
        'user' => $user,
    ]);
})

?>

@props([
    'articleTypeGameHash' => 10,
    'gameWithSortedHashes' => null, // Game
    'user' => null, // User
])

<x-app-layout pageTitle="{{ 'Manage Game Hashes - ' . $game->title }}">
    <div>
        <x-game.breadcrumbs
            :game="$game"
            currentPageLabel="Manage Hashes"
        />

        <div class="mt-3 mb-1 w-full flex gap-x-3">
            {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
            <h1 class="mt-[10px] w-full">Manage Hashes</h1>
        </div>

        <div class="mb-4">
            <x-game.link-buttons.index
                :allowedLinks="['forum-topic', 'game-files']"
                :game="$gameWithSortedHashes"
                variant="row"
            />
        </div>

        <div class="mb-6 flex gap-x-4">
            <x-alert variant="destructive" title="Warning">
                <div class="flex flex-col">
                    <p>
                        PLEASE be careful when using this tool. Mistakes can cause a lot of tickets to be created.
                    </p>

                    <p>
                        If you're not <span class="underline font-semibold">100% sure</span> of what you're doing,
                        <a href="{{ route('message.create') . '?to=RAdmin&subject=Help+with+Hash+Management+for+' . urlencode($game->title) . '&message=%5Bgame=' . $game->ID . '%5D' }}">
                            contact RAdmin
                        </a>
                        and they'll help you out.
                    </p>
                </div>
            </x-alert>
        </div>

        <hr class="border-embed-highlight mb-4" />

        <p class="mb-4">
            <span class="font-bold"><x-game-title :rawTitle="$game->title" /></span>
            currently has
            <span class="font-bold">{{ count($game->hashes) }}</span>
            unique
            {{ mb_strtolower(__res('game-hash', count($game->hashes))) }}
            associated to it.
        </p>

        <div class="mb-10">
            <x-manage-hashes.hashes-list
                :gameId="$game->id"
                :hashes="$game->hashes"
                :myUsername="$user->username"
            />
        </div>

        <div>
            <x-comment.list articleType="{{ ArticleType::GameHash }}" articleId="{{ $game->id }}" />
        </div>
    </div>
</x-app-layout>
