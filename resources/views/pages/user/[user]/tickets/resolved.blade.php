<?php

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('developer.tickets.resolved');

render(function (View $view, User $user, TicketListService $ticketListService) {
    $ticketListService->perPage = 50;
    $selectFilters = $ticketListService->getSelectFilters(showStatus: false, showDevType: false, showDeveloper: true, showReporter: true);
    $filterOptions = $ticketListService->getFilterOptions(request());
    $filterOptions['status'] = 'all'; // will be filtered to Resolved below (status=resolved includes closed tickets)
    $filterOptions['userId'] = $user->id;

    $ticketQuery = $user->resolvedTickets()->getQuery()
        ->where('ReportState', '=', TicketState::Resolved);

    $tickets = $ticketListService->getTickets($filterOptions, $ticketQuery);

    return $view->with([
        'pageTitle' => 'Tickets Resolved',
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
    'pageTitle' => 'Tickets Resolved for Others',
    'user' => null, // User
    'tickets' => null, // Collection<int, Ticket>
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
])

<x-app-layout pageTitle="{{ $pageTitle }} - {{ $user->display_name }}">
    <x-user.breadcrumbs
        :targetDisplayName="$user->display_name"
        :currentPage="$pageTitle"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! userAvatar($user, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
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
