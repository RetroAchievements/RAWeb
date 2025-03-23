<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Observers;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class EventAchievementObserverTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    private function copyAchievementUnlocksToEventAchievement(Achievement $achievement, ?Achievement $sourceAchievement, ?Carbon $activeFrom, ?Carbon $activeUntil): void
    {
        // assert: this will trigger the observer
        $eventAchievement = EventAchievement::updateOrCreate(
            ['achievement_id' => $achievement->id],
            [
                'source_achievement_id' => $sourceAchievement?->id,
                'active_from' => $activeFrom,
                'active_until' => $activeUntil,
            ],
        );
    }

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

        $day1 = Carbon::now()->subDays(3)->startOfDay();
        $day2 = $day1->clone()->addDays(1);
        $day3 = $day2->clone()->addDays(2);
        $day4 = $day3->clone()->addDays(3);
        $time1 = $day1->clone()->addHours(8)->addMinutes(37)->addSeconds(13);
        $time2 = $day2->clone()->addHours(0)->addMinutes(3)->addSeconds(55);
        $time3 = $day3->clone()->addHours(17)->addMinutes(15)->addSeconds(46);

        $this->addHardcoreUnlock($player1, $sourceAchievement, $time1);
        $this->addHardcoreUnlock($player2, $sourceAchievement, $time2);
        $this->addSoftcoreUnlock($player3, $sourceAchievement, $time3);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(0, $achievement->playerAchievements()->count());

        // unbounded attachment should copy all hardcore unlocks
        $this->copyAchievementUnlocksToEventAchievement($achievement, $sourceAchievement, null, null);

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(2, $achievement->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement, UnlockMode::Hardcore));
        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        $this->copyAchievementUnlocksToEventAchievement($achievement2, $sourceAchievement, $day2, null); // anything on day2 or later

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement2->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        $this->copyAchievementUnlocksToEventAchievement($achievement3, $sourceAchievement, null, $day2); // anything before day2

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement3->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement3, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        $this->copyAchievementUnlocksToEventAchievement($achievement4, $sourceAchievement, $day2, $day3); // anything on day2

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(1, $achievement4->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement4, UnlockMode::Hardcore));

        // bounded attachment should only copy hardcore unlocks in range
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $eventGame->id]);
        $this->copyAchievementUnlocksToEventAchievement($achievement5, $sourceAchievement, $day3, $day4); // anything on day3

        $this->assertEquals(3, $sourceAchievement->playerAchievements()->count());
        $this->assertEquals(0, $achievement5->playerAchievements()->count());

        // unlocking achievement in hardcore should propogate to all active events
        /** @var User $player4 */
        $player4 = User::factory()->create();
        $time2b = $time2->clone()->addMinutes(10);
        $this->addHardcoreUnlock($player4, $sourceAchievement, $time2b);

        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement, UnlockMode::Hardcore)); // unbounded
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore)); // $day2 or after
        $this->assertEquals(null, $this->getUnlockTime($player4, $achievement3, UnlockMode::Hardcore)); // before day2
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement4, UnlockMode::Hardcore)); // on day2
        $this->assertEquals(null, $this->getUnlockTime($player4, $achievement5, UnlockMode::Hardcore)); // on day3

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
        $this->copyAchievementUnlocksToEventAchievement($achievement2, $sourceAchievement, $day3, null); // anything on day3 or later

        $this->assertEquals(2, $achievement2->playerAchievements()->count());

        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore));

        // adjusting date range to be more inclusive does add new unlocks
        $this->copyAchievementUnlocksToEventAchievement($achievement2, $sourceAchievement, $day1, null); // anything on day1 or later

        $this->assertEquals(3, $achievement2->playerAchievements()->count());

        $this->assertEquals($time1, $this->getUnlockTime($player1, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2, $this->getUnlockTime($player2, $achievement2, UnlockMode::Hardcore));
        $this->assertEquals($time2b, $this->getUnlockTime($player4, $achievement2, UnlockMode::Hardcore));
    }
}
