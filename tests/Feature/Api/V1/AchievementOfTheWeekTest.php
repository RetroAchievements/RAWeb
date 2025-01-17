<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\StaticData;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AchievementOfTheWeekTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testGetAchievementOfTheWeekEmptyResponse(): void
    {
        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null], 'StartAt' => null]);
    }

    public function testGetAchievementOfTheWeekEventAchievement(): void
    {
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        $now = Carbon::now();
        $time1 = $now->clone()->startOfSecond();
        $this->addHardcoreUnlock($this->user, $achievement1, $time1);
        $time2 = $time1->clone()->subMinutes(5);
        $this->addSoftcoreUnlock($user2, $achievement1, $time2);
        $time3 = $time2->clone()->addMinutes(10);
        $this->addHardcoreUnlock($user3, $achievement1, $time3);

        $staticData = StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement2->ID,
            'Event_AOTW_StartAt' => $now->clone()->subDays(2),
            'Event_AOTW_ForumID' => 2,
        ]);

        System::factory()->create(['ID' => System::Events]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['ConsoleID' => System::Events, 'Title' => 'Achievement of the Week', 'ForumTopicId' => 1]);
        /** @var Achievement $eventAchievement1 */
        $eventAchievement1 = Achievement::factory()->published()->create(['GameID' => $eventGame->ID]);

        $ev = EventAchievement::create([
            'achievement_id' => $eventAchievement1->ID,
            'source_achievement_id' => $achievement1->ID,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // AotW event achievement will only have hardcore unlocks.
        // normally they're unlocked by the source achievement, so do it manually for the test
        $this->addHardcoreUnlock($user3, $eventAchievement1, $now->copy()->addMinutes(5));

        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement1->ID, // event achievement used over static data
                ],
                'Console' => [
                    'ID' => $game->id, // console comes from source achievement game
                ],
                'ForumTopic' => [
                    'ID' => 1, // forum topic from from event game
                ],
                'Game' => [
                    'ID' => $game->ID, // source achievement game
                ],
                'StartAt' => $ev->active_from->jsonSerialize(),
                'TotalPlayers' => 2, // only the hardcore unlock applies (event achievements can't technically be unlocked in softcore)
                'Unlocks' => [
                    [
                        'User' => $user3->User,
                        'RAPoints' => $user3->RAPoints,
                        'RASoftcorePoints' => $user3->RASoftcorePoints,
                        'HardcoreMode' => 1,
                        'DateAwarded' => $time3->jsonSerialize(),
                    ],
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'RASoftcorePoints' => $this->user->RASoftcorePoints,
                        'HardcoreMode' => 1,
                        'DateAwarded' => $time1->jsonSerialize(),
                    ],
                ],
                'UnlocksCount' => 2,
                'UnlocksHardcoreCount' => 2,
            ]);
    }
}
