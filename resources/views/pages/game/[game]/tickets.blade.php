<?php

use function Laravel\Folio\{name};

name('game.tickets');

?>

@props([
    'game' => null, // Game
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'tickets' => [], // Collection<Ticket>
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
])

<x-app-layout pageTitle="Tickets - {{ $game->Title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Tickets"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! gameAvatar($game, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Tickets</h1>
    </div>

    <x-meta-panel
        :availableSelectFilters="$availableSelectFilters"
        :filterOptions="$filterOptions"
    />

    <x-ticket.ticket-list
        :tickets="$tickets"
        :totalTickets="$totalTickets"
        :numFilteredTickets="$numFilteredTickets"
    />
</x-app-layout>
