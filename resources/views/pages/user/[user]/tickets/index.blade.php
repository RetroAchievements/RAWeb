<?php

use App\Models\User;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render, withTrashed};

withTrashed();

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('developer.tickets');

render(function (View $view, User $user, TicketListService $ticketListService) {
    $ticketListService->perPage = 50;
    $selectFilters = $ticketListService->getSelectFilters(showDevType: false);
    $filterOptions = $ticketListService->getFilterOptions(request());
    $tickets = $ticketListService->getTickets($filterOptions, Ticket::forDeveloper($user));

    return $view->with([
        'user' => $user,
        'tickets' => $tickets,
        'availableSelectFilters' => $selectFilters,
        'filterOptions' => $filterOptions,
        'totalTickets' => $ticketListService->totalTickets,
        'numFilteredTickets' => $ticketListService->numFilteredTickets,
        'currentPage' => $ticketListService->pageNumber,
        'totalPages' => $ticketListService->totalPages,
    ]);
});

?>

@props([
    'user' => null, // User
    'tickets' => null, // Collection<int, Ticket>
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
])

<x-app-layout pageTitle="Tickets - {{ $user->User }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        currentPage="Tickets"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
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
        :currentPage="$currentPage"
        :totalPages="$totalPages"
        showResolver="{{ ($filterOptions['status'] !== 'unresolved') }}"
    />
</x-app-layout>
