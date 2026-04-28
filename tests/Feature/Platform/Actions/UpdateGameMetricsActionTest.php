<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Jobs\UpdateGameBeatenMetricsJob;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues player and beaten metrics when achievement set version changes', function () {
    // ARRANGE
    Queue::fake();

    $user = User::factory()->create();
    $game = Game::factory()->create();

    Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    Queue::assertPushedOn('game-player-games', UpdateGamePlayerGamesJob::class);
    Queue::assertPushedOn('game-beaten-metrics', UpdateGameBeatenMetricsJob::class);
});
