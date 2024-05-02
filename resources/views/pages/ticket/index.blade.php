<?php

use function Laravel\Folio\{name};

name('ticket.index');

?>

@props([
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'game' => null,
    'dev' => null,
    'sortOrder' => 'title',
])

@php

use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\Ticket;
use App\Platform\Enums\AchievementFlag;

$currentPage = '';

if ($game !== null) {
    $tickets = Ticket::whereHas('achievement', function($query) use ($game) {
        $query->where('GameID', $game->id);
    });
    $currentPage = $game->Title;
} else {
    $tickets = Ticket::query();
}

$totalTickets = $tickets->count();

$statusType = 'All Tickets';
switch ($filterOptions['status']) {
    case 'unresolved':
        $statusType = 'Unresolved Tickets';
        $tickets->unresolved();
        break;

    case 'resolved':
        $statusType = 'Resolved Tickets';
        $tickets->resolved();
        break;

    default:
        $statusType = 'All Tickets';
        break;
}

if ($filterOptions['type'] > 0) {
    $tickets->where('ReportType', $filterOptions['type']);
}

switch ($filterOptions['achievement']) {
    case 'core':
        $tickets->whereHas('achievement', function($query) {
            $query->where('Flags', AchievementFlag::OfficialCore);
        });
        break;

    case 'unofficial':
        $tickets->whereHas('achievement', function($query) {
            $query->where('Flags', AchievementFlag::Unofficial);
        });
        break;
}

switch ($filterOptions['mode']) {
    case 'hardcore':
        $tickets->where('Hardcore', 1);
        break;

    case 'softcore':
        $tickets->where('Hardcore', 0);
        break;

    case 'unspecified':
        $tickets->whereNull('Hardcore');
        break;
}

switch ($filterOptions['developerType']) {
    case 'active':
        $tickets->whereHas('achievement', function($query) {
            $query->whereHas('developer', function($query2) {
                $query2->where('Permissions', '>=', Permissions::JuniorDeveloper);
            });
        });
        break;

    case 'junior':
        $tickets->whereHas('achievement', function($query) {
            $query->whereHas('developer', function($query2) {
                $query2->where('Permissions', '=', Permissions::JuniorDeveloper);
            });
        });
        break;

    case 'inactive':
        $tickets->whereHas('achievement', function($query) {
            $query->whereHas('developer', function($query2) {
                $query2->where('Permissions', '<', Permissions::JuniorDeveloper);
            });
        });
        break;
}

$numFilteredTickets = $tickets->count();
$tickets = $tickets->with(['achievement', 'reporter'])->get();

@endphp

<x-app-layout pageTitle="{{ $statusType }} - {{ $currentPage }}">
    <div class="navpath">
        <a href="/ticketmanager.php">{{ $statusType }}</a>
        &raquo;
        <span class="font-bold">{{ $currentPage }}</span>
    </div>

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Ticket Manager @if ($currentPage) - {{ $currentPage }} @endif</h1>
    </div>

    <x-meta-panel
        :availableSelectFilters="$availableSelectFilters"
        :filterOptions="$filterOptions"
    />

    <x-ticket.ticket-list
        :tickets="$tickets"
        :totalTickets="$totalTickets"
        :numFilteredTickets="$numFilteredTickets"
    />
</x-app-layout>
