<?php

use App\Models\Achievement;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('achievement.tickets');

render(function (View $view, Achievement $achievement) {
    $ticketListService = new TicketListService();
    
    $selectFilters = $ticketListService->getSelectFilters(showDevType: false, showAchievementType: false);
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

<x-app-layout pageTitle="Tickets - {{ $achievement->Title }}">
    <x-achievement.breadcrumbs
        :achievement="$achievement"
        currentPageLabel="Tickets"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
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
        showResolver="{{ ($filterOptions['status'] !== 'unresolved') }}"
    />
</x-app-layout>
