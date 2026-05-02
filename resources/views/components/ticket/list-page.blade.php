@props([
    'tickets' => [],
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => 0,
    'totalPages' => 0,
    'showResolver' => null,
])

@php

use App\Platform\Services\TicketListService;

$showResolver ??= TicketListService::shouldShowResolverColumn($filterOptions);

@endphp

<x-meta-panel
    :$availableSelectFilters
    :$filterOptions
/>

<x-ticket.ticket-list
    :$tickets
    :$totalTickets
    :$numFilteredTickets
    :$currentPage
    :$totalPages
    :$showResolver
/>
