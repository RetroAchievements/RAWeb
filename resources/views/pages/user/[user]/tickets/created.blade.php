<?php

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('user.tickets.created');

render(function (View $view, User $user, TicketListService $ticketListService) {
    $ticketListService->perPage = 50;
    $selectFilters = $ticketListService->getSelectFilters(showDevType: false);
    $filterOptions = $ticketListService->getFilterOptions(request());

    $ticketQuery = $user->reportedTickets()->getQuery();

    $tickets = $ticketListService->getTickets($filterOptions, $ticketQuery);

    return $view->with([
        'pageTitle' => "Tickets Created",
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
    'pageTitle' => 'Tickets Created',
    'user' => null, // User
    'tickets' => null, // Collection<int, Ticket>
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
])

<x-app-layout pageTitle="{{ $pageTitle }} - {{ $user->User }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        :currentPage="$pageTitle"
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
        showResolver="true"
    />
</x-app-layout>
