<?php

use function Laravel\Folio\{name};

name('achievement.tickets');

?>

@props([
    'achievement' => null, // Achievement
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'tickets' => [], // Collection<Ticket>
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
])

<x-app-layout pageTitle="Tickets - {{ $achievement->Title }}">
    <x-achievement.breadcrumbs
        :achievement="$achievement"
        currentPageLabel="Tickets"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
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
        showResolver="{{ ($filterOptions['status'] !== 'unresolved') }}"
    />
</x-app-layout>
