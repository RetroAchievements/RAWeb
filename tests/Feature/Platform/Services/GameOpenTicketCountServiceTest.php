<?php

declare(strict_types=1);

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketableType;
use App\Platform\Services\GameOpenTicketCountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('counts published open achievement and leaderboard tickets for a game', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'author_id' => $developer->id,
        'state' => LeaderboardState::Active,
    ]);

    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);
    Ticket::factory()->create([
        'ticketable_type' => TicketableType::Leaderboard->value,
        'ticketable_id' => $leaderboard->id,
        'state' => TicketState::Request,
    ]);

    // Assert
    expect(app(GameOpenTicketCountService::class)->count($game, true))->toBe(2);
});

it('counts unpublished open achievement and leaderboard tickets for a game', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
        'is_promoted' => false,
    ]);
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'author_id' => $developer->id,
        'state' => LeaderboardState::Unpromoted,
    ]);

    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);
    Ticket::factory()->create([
        'ticketable_type' => TicketableType::Leaderboard->value,
        'ticketable_id' => $leaderboard->id,
        'state' => TicketState::Request,
    ]);

    // Assert
    expect(app(GameOpenTicketCountService::class)->count($game, false))->toBe(2);
});

it('excludes tickets that are not open or pending reporter feedback', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);

    foreach ([TicketState::Resolved, TicketState::Closed, TicketState::Quarantined] as $state) {
        Ticket::factory()->create([
            'ticketable_id' => $achievement->id,
            'state' => $state,
        ]);
    }

    // Assert
    expect(app(GameOpenTicketCountService::class)->count($game, true))->toBe(0);
});

it('invalidates the cached count when a ticket is created', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($game, true))->toBe(0);

    // Act
    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);

    // Assert
    expect($service->count($game, true))->toBe(1);
});

it('invalidates the cached count when a ticket leaves an open state', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);
    $ticket = Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($game, true))->toBe(1);

    // Act
    $ticket->state = TicketState::Resolved;
    $ticket->save();

    // Assert
    expect($service->count($game, true))->toBe(0);
});

it('invalidates the cached count when an achievement is promoted', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
        'is_promoted' => false,
    ]);
    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($game, false))->toBe(1);
    expect($service->count($game, true))->toBe(0);

    // Act
    $achievement->is_promoted = true;
    $achievement->save();

    // Assert
    expect($service->count($game, false))->toBe(0);
    expect($service->count($game, true))->toBe(1);
});

it('invalidates both source and destination caches when an achievement moves between games', function () {
    // Arrange
    $developer = User::factory()->create();
    $system = System::factory()->create();
    $sourceGame = Game::factory()->create(['system_id' => $system->id]);
    $destinationGame = Game::factory()->create(['system_id' => $system->id]);
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => $sourceGame->id,
        'user_id' => $developer->id,
    ]);
    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($sourceGame, true))->toBe(1);
    expect($service->count($destinationGame, true))->toBe(0);

    // Act
    $achievement->game_id = $destinationGame->id;
    $achievement->save();

    // Assert
    expect($service->count($sourceGame, true))->toBe(0);
    expect($service->count($destinationGame, true))->toBe(1);
});

it('invalidates the cached count when a leaderboard changes promotion state', function () {
    // Arrange
    $developer = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'author_id' => $developer->id,
        'state' => LeaderboardState::Unpromoted,
    ]);
    Ticket::factory()->create([
        'ticketable_type' => TicketableType::Leaderboard->value,
        'ticketable_id' => $leaderboard->id,
        'state' => TicketState::Open,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($game, false))->toBe(1);
    expect($service->count($game, true))->toBe(0);

    // Act
    $leaderboard->state = LeaderboardState::Active;
    $leaderboard->save();

    // Assert
    expect($service->count($game, false))->toBe(0);
    expect($service->count($game, true))->toBe(1);
});

it('invalidates both source and destination caches when a leaderboard moves between games', function () {
    // Arrange
    $developer = User::factory()->create();
    $system = System::factory()->create();
    $sourceGame = Game::factory()->create(['system_id' => $system->id]);
    $destinationGame = Game::factory()->create(['system_id' => $system->id]);
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $sourceGame->id,
        'author_id' => $developer->id,
        'state' => LeaderboardState::Active,
    ]);
    Ticket::factory()->create([
        'ticketable_type' => TicketableType::Leaderboard->value,
        'ticketable_id' => $leaderboard->id,
        'state' => TicketState::Open,
    ]);

    $service = app(GameOpenTicketCountService::class);

    expect($service->count($sourceGame, true))->toBe(1);
    expect($service->count($destinationGame, true))->toBe(0);

    // Act
    $leaderboard->game_id = $destinationGame->id;
    $leaderboard->save();

    // Assert
    expect($service->count($sourceGame, true))->toBe(0);
    expect($service->count($destinationGame, true))->toBe(1);
});
