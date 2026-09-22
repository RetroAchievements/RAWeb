<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Jobs\BackfillEventAchievementUnlocksJob;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return array{Achievement, Achievement}
 */
function createEventAchievementTestPair(bool $eventPromoted = true): array
{
    $system = System::factory()->create();
    $eventSystem = System::find(System::Events) ?? System::factory()->create(['id' => System::Events]);
    $sourceGame = Game::factory()->create(['system_id' => $system->id]);
    $eventGame = Game::factory()->create(['system_id' => $eventSystem->id]);
    $sourceAchievement = Achievement::factory()->promoted()->create(['game_id' => $sourceGame->id]);
    $eventAchievement = Achievement::factory()->create([
        'game_id' => $eventGame->id,
        'is_promoted' => $eventPromoted,
    ]);

    return [$sourceAchievement, $eventAchievement];
}

describe('Event Achievement Observer', function (): void {
    it('given a source achievement is attached, queues one backfill job', function (): void {
        // ARRANGE
        [$sourceAchievement, $eventAchievement] = createEventAchievementTestPair();
        Queue::fake();

        // ACT
        EventAchievement::create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
        ]);

        // ASSERT
        Queue::assertPushedOn('event-backfill', BackfillEventAchievementUnlocksJob::class);
        Queue::assertPushed(BackfillEventAchievementUnlocksJob::class, 1);
        Queue::assertNotPushed(UnlockPlayerAchievementJob::class);
    });

    it('given an unpromoted event achievement, does not queue a backfill job', function (): void {
        // ARRANGE
        [$sourceAchievement, $eventAchievement] = createEventAchievementTestPair(eventPromoted: false);
        Queue::fake();

        // ACT
        EventAchievement::create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
        ]);

        // ASSERT
        Queue::assertNotPushed(BackfillEventAchievementUnlocksJob::class);
    });

    it('given only the decorator changed, does not queue a backfill job', function (): void {
        // ARRANGE
        [$sourceAchievement, $eventAchievement] = createEventAchievementTestPair();
        $eventAchievement = EventAchievement::withoutEvents(fn (): EventAchievement => EventAchievement::create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
        ]));
        $eventAchievement = EventAchievement::findOrFail($eventAchievement->id);
        Queue::fake();

        // ACT
        $eventAchievement->update(['decorator' => 'foo']);

        // ASSERT
        Queue::assertNothingPushed();
    });
});
