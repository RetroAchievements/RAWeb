<?php

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Services\GameTopAchieversService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game']);
name('game.masters');

render(function (View $view, Game $game, GameTopAchieversService $service) {
    $service->initialize($game);
    $numMasters = $service->numMasteries();

    $validatedData = request()->validate([
        'page.number' => 'sometimes|integer|min:1',
    ]);
    $currentPage = $validatedData['page']['number'] ?? 1;
    $offset = ($currentPage - 1) * 50;

    return $view->with([
        'masters' => $service->allMasteries($offset, 50),
        'numMasters' => $numMasters,
        'currentPage' => $currentPage,
        'numPages' => (int) (($numMasters + 49) / 50),
    ]);
});

?>

@props([
    'masters' => [], // Collection<PlayerGame>
    'numMasters' => 0,
    'pageNumber' => 1,
    'currentPage' => 1,
])

<x-app-layout
    pageTitle="Masters - {{ $game->title }}"
    pageDescription="A list of people who have mastered {{ $game->title }}"
>
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Masters"
    />

    <div class="mt-3 -mb-3 w-full flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Game Masteries</h1>
    </div>

    <div>
        <table class='table-highlight'>
            <thead>
                <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[11] bg-box">
                    <th class="text-right">#</th>
                    <th>User</th>
                    <th>Mastered</th>
                </tr>
            </thead>
            <tbody>

            @php $rank = ($currentPage - 1) * 50 + 1 @endphp
            @foreach ($masters as $mastery)
                <x-game.top-achievers.mastery-row
                    :rank="$rank"
                    :masteryUser="$mastery->user"
                    :masteryDate="$mastery->last_unlock_hardcore_at"
                    icon-size="sm"
                />

                @php
                    $rank++;
                @endphp
            @endforeach

            </tbody>
        </table>
    </div>

    @if ($numPages > 1)
        <div class="w-full flex items-center justify-end">
            <x-paginator :totalPages="$numPages" :currentPage="$currentPage" />
        </div>
    @endif
</x-app-layout>
