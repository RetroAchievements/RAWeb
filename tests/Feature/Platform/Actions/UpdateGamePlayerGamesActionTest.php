<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdateGamePlayerGamesAction;
use App\Platform\Actions\UpdatePlayerGameMetricsChunkAction;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdateGameBeatenMetricsJob;
use App\Platform\Jobs\UpdateGamePlayerCountJob;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

it('given a game has no player games, dispatches GamePlayerGameMetricsUpdated', function () {
    // ARRANGE
    Event::fake();

    $game = $this->seedGame(achievements: 1);

    // ACT
    (new UpdateGamePlayerGamesAction())->execute($game);

    // ASSERT
    Event::assertDispatched(
        GamePlayerGameMetricsUpdated::class,
        fn (GamePlayerGameMetricsUpdated $event): bool => $event->game->is($game),
    );
});

it('given all player game metric batches complete, dispatches GamePlayerGameMetricsUpdated once', function () {
    // ARRANGE
    Event::fake();

    $game = $this->seedGame(achievements: 1);
    $users = User::factory()->count(1001)->create();

    foreach ($users as $user) {
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 0,
        ]);
    }

    // ACT
    (new UpdateGamePlayerGamesAction())->execute($game);

    // ASSERT
    Event::assertDispatchedTimes(GamePlayerGameMetricsUpdated::class, 1);
});

it('given a non-empty player games refresh completes, queues UpdateGameBeatenMetricsJob', function () {
    // ARRANGE
    Queue::fake([
        UpdateGamePlayerCountJob::class,
        UpdateGameBeatenMetricsJob::class,
    ]);

    $game = $this->seedGame(achievements: 1);
    $user = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 0,
    ]);

    // ACT
    (new UpdateGamePlayerGamesAction())->execute($game);

    // ASSERT
    Queue::assertPushedOn('game-beaten-metrics', UpdateGameBeatenMetricsJob::class);
});

it('given the prepared run is superseded by a set hash change, does not fire GamePlayerGameMetricsUpdated', function () {
    // ARRANGE
    Event::fake();

    $game = $this->seedGame(achievements: 1);
    $user = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
    ]);

    $game->achievement_set_version_hash = 'stale-hash'; // !! hash drift

    // ACT
    (new UpdateGamePlayerGamesAction())->execute($game);

    // ASSERT
    Event::assertNotDispatched(GamePlayerGameMetricsUpdated::class);
});

it('given the set hash changes after chunks complete, does not fire GamePlayerGameMetricsUpdated', function () {
    // ARRANGE
    Event::fake();

    $game = $this->seedGame(achievements: 1);
    $user = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
    ]);

    /**
     * mutate the hash after the chunk's own hash-drift check has passed.
     * this forces us to exercise the finally()'s hash-drift check.
     */
    $fake = Mockery::mock(UpdatePlayerGameMetricsChunkAction::class);
    /** @var Mockery\Expectation $execute */
    $execute = $fake->shouldReceive('execute');
    $execute->andReturnUsing(function () use ($game) {
        Game::where('id', $game->id)
            ->update(['achievement_set_version_hash' => 'newer-hash']);
    });
    app()->instance(UpdatePlayerGameMetricsChunkAction::class, $fake);

    // ACT
    (new UpdateGamePlayerGamesAction())->execute($game);

    // ASSERT
    Event::assertNotDispatched(GamePlayerGameMetricsUpdated::class);
});
