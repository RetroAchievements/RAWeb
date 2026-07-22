<?php

use App\Models\Achievement;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('achievement.tickets');

render(function (View $view, Achievement $achievement, TicketListService $ticketListService) {
    $selectFilters = $ticketListService->getSelectFilters(showDevType: false, showPublishedStatus: false, systemId: $achievement->game->system->id);
    $filterOptions = $ticketListService->getFilterOptions(request());
    $tickets = $ticketListService->getTickets($filterOptions, Ticket::forAchievement($achievement));

    return $view->with([
        'achievement' => $achievement,
        'tickets' => $tickets,
        'availableSelectFilters' => $selectFilters,
        'filterOptions' => $filterOptions,
        'totalTickets' => $ticketListService->totalTickets,
        'numFilteredTickets' => $ticketListService->numFilteredTickets,
    ]);
});

?>

@props([
    'achievement' => null, // Achievement
    'tickets' => null, // Collection<int, Ticket>
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
])

<x-app-layout pageTitle="Tickets - {{ $achievement->title }}">
    <x-achievement.breadcrumbs
        :$achievement
        currentPageLabel="Tickets"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($achievement, label: false, iconSize: 48, iconClass: 'rounded-xs') !!}
        <h1 class="mt-[10px] w-full">Tickets</h1>
    </div>

    <x-ticket.list-page
        :$tickets
        :$availableSelectFilters
        :$filterOptions
        :$totalTickets
        :$numFilteredTickets
    />
</x-app-layout>
