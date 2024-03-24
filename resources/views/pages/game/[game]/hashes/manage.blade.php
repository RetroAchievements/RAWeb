<?php

use App\Community\Enums\ArticleType;
use App\Models\Game;
use App\Models\GameHash;
use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,game', 'can:manage,' . GameHash::class]);
name('game.hash.manage');

?>

@php
$gameWithSortedHashes = $game->load([
    'hashes' => function ($query) {
        $query->orderBy('Name');
    },
    'hashes.user'
]);

$user = request()->user();

$articleTypeGameHash = ArticleType::GameHash;
$numLogs = getRecentArticleComments($articleTypeGameHash, $game->ID, $logs);
@endphp

<x-app-layout pageTitle="{{ 'Manage Game Hashes - ' . $game->Title }}">
    <div>
        <x-game.breadcrumbs
            :targetConsoleId="$game->system->ID"
            :targetConsoleName="$game->system->Name"
            :targetGameId="$game->ID"
            :targetGameName="$game->Title"
            currentPageLabel="Manage Hashes"
        />

        <div class="mt-3 mb-1 w-full flex gap-x-3">
            {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
            <h1 class="mt-[10px] w-full">Manage Hashes</h1>
        </div>

        <div class="mb-4">
            <x-game.link-buttons.index
                :allowedLinks="['forum-topic', 'game-files']"
                :gameId="$game->ID"
                :gameTitle="$game->Title"
                :gameForumTopicId="$game->ForumTopicID"
                variant="row"
            />
        </div>

        <div class="mb-6 flex gap-x-4">
            <x-alert variant="destructive">
                <x-slot name="title">Warning</x-slot>
                <x-slot name="description">
                    <div class="flex flex-col">
                        <p>
                            PLEASE be careful when using this tool. Mistakes can cause a lot of tickets to be created.
                        </p>

                        <p>
                            If you're not <span class="underline font-semibold">100% sure</span> of what you're doing,
                            <a href="{{ route('message.create') . '?to=RAdmin&subject=Help+with+Hash+Management+for+' . urlencode($game->Title) . '&message=%5Bgame=' . $game->ID . '%5D' }}">
                                contact RAdmin
                            </a>
                            and they'll help you out.
                        </p>
                    </div>
                </x-slot>
            </x-alert>
        </div>

        <hr class="border-embed-highlight mb-4" />

        <p class="mb-4">
            <span class="font-bold"><x-game-title :rawTitle="$game->Title" /></span>
            currently has
            <span class="font-bold">{{ count($game->hashes) }}</span>
            unique
            {{ mb_strtolower(__res('game-hash', count($game->hashes))) }}
            associated to it.
        </p>

        <div class="mb-10">
            <x-manage-hashes.hashes-list
                :gameId="$game->ID"
                :hashes="$game->hashes"
                :myUsername="$user->username"
            />
        </div>

        <div>
            @php
                RenderCommentsComponent(
                    $user->username,
                    $numLogs,
                    $logs,
                    $game->ID,
                    $articleTypeGameHash,
                    $user->Permissions,
                )
            @endphp
        </div>
    </div>
</x-app-layout>
