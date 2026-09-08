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
    $response = get(route('tickets.index'));

    // ASSERT
    $response->assertRedirect(route('login'));
});

it('given an authenticated user, the list renders with scope all, at most fifty items, and every state count', function () {
    // ARRANGE
    createTicketListPageTickets(130);
    actingAs(User::factory()->create());

    // ACT
    $response = get(route('tickets.index'));

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
            ->has('open')
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

it('given a hub or event game, the game route returns a 404', function (int $systemId) {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => $systemId]);
    actingAs(User::factory()->create());

    // ACT
    $response = get(route('game.tickets', ['game' => $game->id]));

    // ASSERT
    $response->assertNotFound();
})->with([
    'hub' => System::Hubs,
    'event' => System::Events,
]);

it('uses persisted display preferences for the initial ticket list', function () {
    // ARRANGE
    $tickets = createTicketListPageTickets(2);
    actingAs(User::factory()->create());
    $cookieName = 'datatable_view_preference_tickets_all';
    $preferences = [
        'columnVisibility' => ['game' => false],
        'sortParam' => 'createdAt',
    ];

    // ACT
    $response = $this
        ->withUnencryptedCookie($cookieName, json_encode($preferences))
        ->get(route('tickets.index'));

    // ASSERT
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('persistenceCookieName', $cookieName)
        ->where('persistedViewPreferences', $preferences)
        ->where('paginatedTickets.items.0.id', $tickets[0]->id)
        ->where('paginatedTickets.items.1.id', $tickets[1]->id)
    );
});

it("given a scoped list, reads that scope's preference cookie", function () {
    // ARRANGE
    $tickets = createTicketListPageTickets(2);
    $game = $tickets[0]->getTicketableModel()->game;
    actingAs(User::factory()->create());

    $scopedPreferences = ['columnVisibility' => ['hash' => true], 'sortParam' => 'createdAt'];
    $globalPreferences = ['columnVisibility' => ['emulator' => true], 'sortParam' => 'state'];

    // ACT
    $response = $this
        ->withUnencryptedCookie('datatable_view_preference_tickets_game', json_encode($scopedPreferences))
        ->withUnencryptedCookie('datatable_view_preference_tickets_all', json_encode($globalPreferences))
        ->get(route('game.tickets', ['game' => $game->id]));

    // ASSERT
    $response->assertInertia(fn (Assert $page) => $page
        ->where('persistenceCookieName', 'datatable_view_preference_tickets_game')
        ->where('persistedViewPreferences', $scopedPreferences)
    );
});

it('given a guest, inbox redirects to login', function () {
    // ACT
    $response = get(route('tickets.mine'));

    // ASSERT
    $response->assertRedirect(route('login'));
});

it('given an authenticated user, inbox renders every section for that user', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    actingAs($viewer);

    // ACT
    $response = get(route('tickets.mine'));

    // ASSERT
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tickets/mine')
        ->has('sections', 5)
        ->where('sectionLimit', 8)
        ->where('attentionCount', 0)
        ->where('user.displayName', $viewer->display_name)
    );
});

it('given a user param, the inbox reports on that user rather than the currently authenticated user', function () {
    // ARRANGE
    $target = User::factory()->create();
    actingAs(User::factory()->create());

    // ACT
    $response = get(route('tickets.mine', ['user' => $target->display_name]));

    // ASSERT
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('user.displayName', $target->display_name)
    );
});

it('given the open status filter, returns only open tickets while unresolved also returns requests', function () {
    // ARRANGE
    createTicketListPageTickets(10);
    actingAs(User::factory()->create());

    // ACT
    $openResponse = get(route('tickets.index', ['filter' => ['status' => 'open']]));
    $unresolvedResponse = get(route('tickets.index', ['filter' => ['status' => 'unresolved']]));

    // ASSERT
    $openTotal = $openResponse->viewData('page')['props']['paginatedTickets']['total'];
    $unresolvedTotal = $unresolvedResponse->viewData('page')['props']['paginatedTickets']['total'];
    $requestCount = $openResponse->viewData('page')['props']['stateCounts']['request'];

    expect($requestCount)->toBeGreaterThan(0);
    expect($unresolvedTotal)->toEqual($openTotal + $requestCount);
});
