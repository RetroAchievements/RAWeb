<?php

use function Laravel\Folio\{name};

name('developer.tickets');

?>

@props([
    'user' => null, // User
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'tickets' => [], // Collection<Ticket>
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => null,
    'totalPages' => null,
    'pageTitle' => 'Tickets',
])

<x-app-layout pageTitle="{{ $pageTitle }} - {{ $user->User }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        currentPage="{{ $pageTitle }}"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $pageTitle }}</h1>
    </div>

    <x-meta-panel
        :availableSelectFilters="$availableSelectFilters"
        :filterOptions="$filterOptions"
    />

    <x-ticket.ticket-list
        :tickets="$tickets"
        :totalTickets="$totalTickets"
        :numFilteredTickets="$numFilteredTickets"
        :currentPage="$currentPage"
        :totalPages="$totalPages"
    />
</x-app-layout>
