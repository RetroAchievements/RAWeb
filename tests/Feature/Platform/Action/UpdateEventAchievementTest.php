<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateEventAchievement;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UpdateEventAchievementTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testAttachSourceAchievement(): void
    {
        /** @var User $player1 */
        $player1 = User::factory()->create();
        /** @var User $player2 */
        $player2 = User::factory()->create();
        /** @var User $player3 */
        $player3 = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();
        /** @var System $eventSystem */
        $eventSystem = System::factory()->create(['ID' => System::Events]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        /** @var Achievement $sourceAchievement */
        $sourceAchievement = Achievement::factory()->published()->create(['GameID' => $game->id]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['ConsoleID' => $eventSystem->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);

        $time1 = Carbon::now()->subDays(1)->startOfSecond();
        $time2 = $time1->clone()->addHours(6);
        $time3 = $time2->clone()->addHours(3);
        $time4 = $time3->clone()->addHours(1);

        $this->addHardcoreUnlock($player1, $sourceAchievement, $time1);
        $this->addHardcoreUnlock($player2, $sourceAchievement, $time2);
        $this->addSoftcoreUnlock($player3, $sourceAchievement, $time3);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(0, $achievement->playerAchievements()->count());

        // unbounded attachment should copy all hardcore unlocks
        (new UpdateEventAchievement())->execute($achievement, $sourceAchievement, null, null);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(2, $achievement->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement, UnlockMode::Hardcore));
        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        (new UpdateEventAchievement())->execute($achievement2, $sourceAchievement, $time2, null);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement2->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        (new UpdateEventAchievement())->execute($achievement3, $sourceAchievement, null, $time2);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement3->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement3, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        (new UpdateEventAchievement())->execute($achievement4, $sourceAchievement, $time2->clone()->subMinutes(5), $time2->clone()->addMinutes(5));

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement4->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement4, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        (new UpdateEventAchievement())->execute($achievement5, $sourceAchievement, $time2->clone()->addMinutes(5), $time4);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(0, $achievement5->playerAchievements()->count());

        // unlocking achievement in hardcore should propogate to all active events
        /** @var User $player4 */
        $player4 = User::factory()->create();
        $time2b = $time2->clone()->addMinutes(10);
        $this->addHardcoreUnlock($player4, $sourceAchievement, $time2b);

        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement, UnlockMode::Hardcore)); // unbounded
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore)); // $time2-
        $this->assertEquals(null, $this->getUnlockTime($player4, $achievement3, UnlockMode::Hardcore)); // -$time2
        $this->assertEquals(null, $this->getUnlockTime($player4, $achievement4, UnlockMode::Hardcore)); // $time2 +/- 5m
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement5, UnlockMode::Hardcore)); // $time2+5 - $time4

        // unlocking achievement in softcore should not propogate to all active events
        /** @var User $player5 */
        $player5 = User::factory()->create();
        $this->addSoftcoreUnlock($player5, $sourceAchievement, $time2b);

        $this->assertDoesNotHaveSoftcoreUnlock($player5, $achievement);
        $this->assertDoesNotHaveSoftcoreUnlock($player5, $achievement2);
        $this->assertDoesNotHaveSoftcoreUnlock($player5, $achievement3);
        $this->assertDoesNotHaveSoftcoreUnlock($player5, $achievement4);
        $this->assertDoesNotHaveSoftcoreUnlock($player5, $achievement5);

        $this->assertDoesNotHaveHardcoreUnlock($player5, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($player5, $achievement2);
        $this->assertDoesNotHaveHardcoreUnlock($player5, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($player5, $achievement4);
        $this->assertDoesNotHaveHardcoreUnlock($player5, $achievement5);

        // adjusting date range to be less inclusive does not remove existing unlocks
        (new UpdateEventAchievement())->execute($achievement2, $sourceAchievement, $time3, null);

        $this->assertEquals(2, $achievement2->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore));

        // adjusting date range to be more inclusive does add new unlocks
        (new UpdateEventAchievement())->execute($achievement2, $sourceAchievement, $time1, null);

        $this->assertEquals(3, $achievement2->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore));
    }
}
