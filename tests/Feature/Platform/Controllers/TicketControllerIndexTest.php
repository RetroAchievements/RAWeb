<?php

declare(strict_types=1);

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(LazilyRefreshDatabase::class);

/**
 * @return Ticket[]
 */
function createTicketListPageTickets(int $ticketCount): array
{
    $developer = User::factory()->create();
    $reporter = User::factory()->create();

    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    $states = [
        TicketState::Open,
        TicketState::Request,
        TicketState::Quarantined,
        TicketState::Resolved,
        TicketState::Closed,
    ];

    $tickets = [];
    for ($i = 0; $i < $ticketCount; $i++) {
        $state = $states[$i % count($states)];
        $isTerminal = in_array($state, [TicketState::Resolved, TicketState::Closed], true);

        $tickets[] = Ticket::factory()
            ->forAchievement($achievement)
            ->create([
                'ticketable_author_id' => $developer->id,
                'reporter_id' => $reporter->id,
                'state' => $state,
                'created_at' => Carbon::parse('2024-01-01 00:00:00')->addHours($i),
                'resolved_at' => $isTerminal ? Carbon::parse('2024-06-01 00:00:00')->addHours($i) : null,
            ]);
    }

    return $tickets;
}

it('given a guest, the index route redirects to login', function () {
    // ACT
    $response = get(route('tickets2.index'));

    // ASSERT
    $response->assertRedirect(route('login'));
});

it('given an authenticated user, the list renders with scope all, at most fifty items, and every state count', function () {
    // ARRANGE
    createTicketListPageTickets(130);
    actingAs(User::factory()->create());

    // ACT
    $response = get(route('tickets2.index'));

    // ASSERT
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tickets')
        ->where('scope', 'all')
        ->where('paginatedTickets.perPage', 50)
        ->has('paginatedTickets.items', 50)
        ->where('paginatedTickets.total', 52)
        ->has('stateCounts', fn (Assert $counts) => $counts
            ->has('unresolved')
            ->has('request')
            ->has('resolved')
            ->has('closed')
            ->has('quarantined')
            ->has('all')
        )
        ->missing('game')
        ->missing('achievement')
        ->missing('user')
    );
});
