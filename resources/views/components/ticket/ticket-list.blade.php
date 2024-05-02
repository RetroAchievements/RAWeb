@props([
    'tickets' => [],
    'totalTickets' => 0,
    'numFilteredTickets' => 0,
    'offset' => 0,
    'currentPage' => 0,
    'totalPages' => 0,
])

@php

use App\Community\Enums\TicketState;
use App\Models\Game;

$gameCache = [];

@endphp

<div>
    <div class="w-full flex mb-2 justify-between">
        <div class="flex items-center">
            <p class="text-xs">
                Viewing
                <span class="font-bold">{{ localized_number($numFilteredTickets) }}</span>
                @if ($numFilteredTickets != $totalTickets)
                    of {{ localized_number($totalTickets) }}
                @endif
                {{ trans_choice(__('resource.ticket.title'), $totalTickets) }}
            </p>
        </div>
        @if ($totalPages)
        <div class="flex items-center">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
        @endif
    </div>

    @if (!empty($tickets))
        <div class="overflow-x-auto lg:overflow-x-visible">
            <table class='table-highlight mb-4'>
                <thead>
                    <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[11] bg-box">
                        <th class="text-right">ID</th>
                        <th>State</th>
                        <th>Achievement</th>
                        <th>Game</th>
                        <th>Developer</th>
                        <th>Reporter</th>
                        <th>Reported At</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td class="text-right">
                                <a href="{{ route('ticket.show', $ticket) }}">{{ $ticket->ID }}</a>
                            </td>
                            <td>{{ TicketState::toString($ticket->ReportState) }}</td>
                            <td>{!! achievementAvatar($ticket->achievement) !!}</td>
                            <td>
                                @php
                                    $game = $gameCache[$ticket->achievement->GameID] ??=
                                        Game::where('ID', $ticket->achievement->GameID)->with('system')->first();
                                @endphp
                                <x-game.multiline-avatar
                                    :gameId="$game->ID"
                                    :gameTitle="$game->Title"
                                    :gameImageIcon="$game->ImageIcon"
                                    :consoleName="$game->system->Name"
                                />
                            </td>
                            <td>{!! userAvatar($ticket->achievement->author) !!}</td>
                            <td>{!! userAvatar($ticket->reporter) !!}</td>
                            <td class="smalldate">{{ getNiceDate($ticket->ReportedAt->unix()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($totalPages)
        <div class="w-full flex items-center justify-end">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    @endif
</div>
