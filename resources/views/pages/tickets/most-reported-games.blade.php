<?php

use App\Models\Game;
use App\Models\Ticket;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\Ticket::class]);
name('tickets.most-reported-games');

render(function (View $view) {
    $ticketedGames = Ticket::unresolved()->officialCore()
        ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
        ->select('GameID', DB::raw('count(*) AS TicketCount'))
        ->groupBy('GameID')
        ->orderBy('TicketCount', 'DESC')
        ->take(100)
        ->get();

    return $view->with([
        'ticketedGames' => $ticketedGames,
    ]);
});

?>

<x-app-layout pageTitle="Most Reported Games">
    <div class="navpath">
        <a href="{{ route('tickets.index') }}">Open Tickets</a>
        &raquo;
        <span class="font-bold">Most Reported Games</span>
    </div>

    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Top 100 Games Sorted by Most Outstanding Tickets</h1>
    </div>

    <div class="overflow-x-auto lg:overflow-x-visible">
        <table class='table-highlight mb-4'>
            <thead>
                <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[11] bg-box">
                    <th>Game</th>
                    <th class="text-right">Number of Open Tickets</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ticketedGames as $ticketedGame)
                    @php $game = Game::firstWhere('ID', $ticketedGame->GameID) @endphp
                    <tr>
                        <td class="py-2">
                            <x-game.multiline-avatar
                                :gameId="$game->ID"
                                :gameTitle="$game->Title"
                                :gameImageIcon="$game->ImageIcon"
                                :consoleName="$game->system->Name"
                            />
                        </td>
                        <td class="text-right">
                            <a href="{{ route('game.tickets', ['game' => $game]) }}?filter%5Bachievement%5D=core">{{ $ticketedGame->TicketCount }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
