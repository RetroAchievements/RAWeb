<?php

use function Laravel\Folio\{name};

name('tickets.index');

?>

@props([
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'tickets' => [], // Collection<Ticket>
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => null,
    'totalPages' => null,
])

@php

use App\Models\Ticket;

$openTicketCount = Ticket::unresolved()->count();

@endphp

<x-app-layout pageTitle="Ticket Manager">
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Ticket Manager - {{ localized_number($openTicketCount) }} Open Tickets</h1>
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
        showResolver="{{ ($filterOptions['status'] !== 'unresolved') }}"
    />
</x-app-layout>
