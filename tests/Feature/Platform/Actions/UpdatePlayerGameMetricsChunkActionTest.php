<?php

declare(strict_types=1);

use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Actions\UpdatePlayerGameMetricsChunkAction;
use App\Platform\Actions\UpdatePlayerMetricsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;

uses(LazilyRefreshDatabase::class);

it('updates each PlayerGame in the chunk and refreshes per-user metrics', function () {
    // ARRANGE
    $game = $this->seedGame(achievements: 1);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user1->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 1,
    ]);
    PlayerGame::factory()->create([
        'user_id' => $user2->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 1,
    ]);

    $gameMetricsSpy = Mockery::spy(UpdatePlayerGameMetricsAction::class);
    app()->instance(UpdatePlayerGameMetricsAction::class, $gameMetricsSpy);
    $playerMetricsSpy = Mockery::spy(UpdatePlayerMetricsAction::class);
    app()->instance(UpdatePlayerMetricsAction::class, $playerMetricsSpy);

    // ACT
    (new UpdatePlayerGameMetricsChunkAction())->execute($game->id, [$user1->id, $user2->id]);

    // ASSERT
    /** @var Mockery\Expectation $gameMetricsExpectation */
    $gameMetricsExpectation = $gameMetricsSpy->shouldHaveReceived('execute');
    $gameMetricsExpectation->twice();

    /** @var Mockery\Expectation $playerMetricsExpectation */
    $playerMetricsExpectation = $playerMetricsSpy->shouldHaveReceived('execute');
    $playerMetricsExpectation->twice();
});

it('given a user has no PlayerGame entity, does not throw', function () {
    // ARRANGE
    $game = $this->seedGame(achievements: 1);
    $user = User::factory()->create();
    // !! no PlayerGame for the user

    $gameMetricsSpy = Mockery::spy(UpdatePlayerGameMetricsAction::class);
    app()->instance(UpdatePlayerGameMetricsAction::class, $gameMetricsSpy);

    // ACT
    (new UpdatePlayerGameMetricsChunkAction())->execute($game->id, [$user->id]);

    // ASSERT
    $gameMetricsSpy->shouldNotHaveReceived('execute');
});

it('given an exception occurs on a single user in the chunk, continues processing the rest of the chunk', function () {
    // ARRANGE
    $game = $this->seedGame(achievements: 1);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user1->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 1,
    ]);
    PlayerGame::factory()->create([
        'user_id' => $user2->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 1,
    ]);

    Exceptions::fake();

    $callCount = 0;
    $gameMetricsMock = Mockery::mock(UpdatePlayerGameMetricsAction::class);
    /** @var Mockery\Expectation $gameMetricsExpectation */
    $gameMetricsExpectation = $gameMetricsMock->shouldReceive('execute');
    $gameMetricsExpectation->andReturnUsing(function () use (&$callCount) {
        $callCount++;
        if ($callCount === 1) {
            throw new RuntimeException('dead :(');
        }
    });
    app()->instance(UpdatePlayerGameMetricsAction::class, $gameMetricsMock);

    $playerMetricsSpy = Mockery::spy(UpdatePlayerMetricsAction::class);
    app()->instance(UpdatePlayerMetricsAction::class, $playerMetricsSpy);

    // ACT
    (new UpdatePlayerGameMetricsChunkAction())->execute($game->id, [$user1->id, $user2->id]);

    // ASSERT
    Exceptions::assertReported(RuntimeException::class);

    expect($callCount)->toEqual(2);
});

it('given the user has no unlocks for the game, takes the fast path', function () {
    // ARRANGE
    $game = $this->seedGame(achievements: 1);
    $user = User::factory()->create();
    PlayerGame::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'achievements_unlocked' => 0,
    ]);

    $gameMetricsSpy = Mockery::spy(UpdatePlayerGameMetricsAction::class);
    app()->instance(UpdatePlayerGameMetricsAction::class, $gameMetricsSpy);

    // ACT
    (new UpdatePlayerGameMetricsChunkAction())->execute($game->id, [$user->id]);

    // ASSERT
    $gameMetricsSpy->shouldNotHaveReceived('execute');
});
