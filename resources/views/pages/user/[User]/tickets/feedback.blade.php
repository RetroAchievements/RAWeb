<?php

use App\Community\Enums\TriggerTicketState;
use App\Models\User;
use App\Models\TriggerTicket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\TriggerTicket::class]);
name('reporter.tickets');

render(function (View $view, User $user, TicketListService $ticketListService) {
    $filterOptions = $ticketListService->getFilterOptions(request());

    $ticketQuery = TriggerTicket::where('reporter_id', '=', $user->id)
        ->where('state', '=', TriggerTicketState::Request);

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

<x-app-layout pageTitle="{{ $pageTitle }} - {{ $user->display_name }}">
    <x-user.breadcrumbs
        :user="$user"
        :currentPage="$pageTitle"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! userAvatar($user, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
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
