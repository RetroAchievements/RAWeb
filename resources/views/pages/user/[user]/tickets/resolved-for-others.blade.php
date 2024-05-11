<?php

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('developer.tickets.resolved-for-others');

render(function (View $view, User $user) {
    $ticketListService = new TicketListService();
    $ticketListService->perPage = 50;
    $selectFilters = $ticketListService->getSelectFilters(showStatus: false);
    $filterOptions = $ticketListService->getFilterOptions(request());
    $filterOptions['status'] = 'all'; // will be filtered to Resolved below

    $ticketQuery = $user->resolvedTickets()->getQuery()
        ->where('ReportState', '=', TicketState::Resolved)
        ->where('reporter_id', '!=', $user->id)
        ->whereHas('achievement', function ($query) use ($user) {
            $query->where('user_id', '!=', $user->id);
        });

    $tickets = $ticketListService->getTickets($filterOptions, $ticketQuery);

    return $view->with([
        'pageTitle' => 'Tickets Resolved for Others',
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
    />
</x-app-layout>
