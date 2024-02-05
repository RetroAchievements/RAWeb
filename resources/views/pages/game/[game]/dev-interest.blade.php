<?php

use App\Community\Enums\ArticleType;
use App\Models\Game;
use App\Models\GameHash;
use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,game', 'can:develop']);
name('game.dev-interest');

?>

@props([
    'gameId' => 0,
    'gameTitle' => 'Unknown Game',
    'consoleId' => 0,
    'consoleName' => 'Unknown Console',
    'imageIcon' => null,
    'users' => [],
])

<x-app-layout
    pageTitle="{{ $gameTitle }} - Developer Interest"
    pageDescription="Developers interested in working on {{ $gameTitle }}"
>
    <div class='navpath'>
        {!! renderGameBreadcrumb(['GameID' => $gameId, 'GameTitle' => $gameTitle, 'ConsoleID' => $consoleId, 'ConsoleName' => $consoleName], true) !!}
        &raquo; <b>Developer Interest</b>
    </div>

    <x-game.heading
        :gameId="$gameId"
        :gameTitle="$gameTitle"
        :consoleId="$consoleId"
        :consoleName="$consoleName"
    />

    <?php $metaKind = 'Game'; ?>
    <x-game.primary-meta
        :imageIcon="$imageIcon"
        :metaKind="$metaKind"
    />

    <p>The following users have added this game to their Want to Develop list:</p>

    <table>
    @if (count($users) < 1)
        <tr><td>None</td></tr>
    @else
        @foreach ($users as $user)
            <tr><td>{!! userAvatar($user) !!}</td></tr>
        @endforeach
    @endif
    </table>
</x-app-layout>
