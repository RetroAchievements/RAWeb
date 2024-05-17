<?php

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('reporter.tickets');

render(function (View $view, User $user) {
    $ticketListService = new TicketListService();
    $filterOptions = $ticketListService->getFilterOptions(request());

    $ticketQuery = Ticket::where('reporter_id', '=', $user->id)
        ->where('ReportState', '=', TicketState::Request);

    $tickets = $ticketListService->getTickets($filterOptions, $ticketQuery);

    return $view->with([
        'pageTitle' => 'Tickets Awaiting Feedback',
        'user' => $user,
        'tickets' => $tickets,
        'filterOptions' => $filterOptions,
        'totalTickets' => $ticketListService->totalTickets,
        'numFilteredTickets' => $ticketListService->numFilteredTickets,
    ]);
});

?>

@props([
    'pageTitle' => 'Tickets Awaiting Feedback',
    'user' => null, // User
    'tickets' => null, // Collection<int, Ticket>
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
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
        :availableSelectFilters="[]"
        :filterOptions="$filterOptions"
    />

    <x-ticket.ticket-list
        :tickets="$tickets"
        :totalTickets="$totalTickets"
        :numFilteredTickets="$numFilteredTickets"
        :currentPage="null"
        :totalPages="null"
    />
</x-app-layout>
