<?php

declare(strict_types=1);

use App\Platform\Actions\BackfillEventAchievementUnlocksAction;
use App\Platform\Jobs\BackfillEventAchievementUnlocksJob;
use Illuminate\Support\Facades\Queue;

it('given the action returns a cursor, queues a continuation from it', function () {
    // ARRANGE
    $action = Mockery::mock(BackfillEventAchievementUnlocksAction::class);
    /** @var Mockery\Expectation $execute */
    $execute = $action->shouldReceive('execute');
    $execute->once()->with(1, null)->andReturn(777);
    app()->instance(BackfillEventAchievementUnlocksAction::class, $action);
    Queue::fake();

    // ACT
    (new BackfillEventAchievementUnlocksJob(1, 2))->handle();

    // ASSERT
    Queue::assertPushed(BackfillEventAchievementUnlocksJob::class, 1);
});

it('given the action returns no cursor, does not queue a continuation', function () {
    // ARRANGE
    $action = Mockery::mock(BackfillEventAchievementUnlocksAction::class);
    /** @var Mockery\Expectation $execute */
    $execute = $action->shouldReceive('execute');
    $execute->once()->with(1, 777)->andReturnNull();
    app()->instance(BackfillEventAchievementUnlocksAction::class, $action);
    Queue::fake();

    // ACT
    (new BackfillEventAchievementUnlocksJob(1, 2, 777))->handle();

    // ASSERT
    Queue::assertNothingPushed();
});
