<?php

use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('tickets.index');

render(function (View $view) {
    $ticketListService = new TicketListService();

    $ticketListService->perPage = 50;
    $selectFilters = $ticketListService->getSelectFilters();
    $filterOptions = $ticketListService->getFilterOptions(request());
    $tickets = $ticketListService->getTickets($filterOptions);

    $openTicketCount = Ticket::unresolved()->count();

    return $view->with([
        'tickets' => $tickets,
        'availableSelectFilters' => $selectFilters,
        'filterOptions' => $filterOptions,
        'totalTickets' => $ticketListService->totalTickets,
        'numFilteredTickets' => $ticketListService->numFilteredTickets,
        'currentPage' => $ticketListService->pageNumber,
        'totalPages' => $ticketListService->totalPages,
        'openTicketCount' => $openTicketCount,
    ]);
});

?>

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
