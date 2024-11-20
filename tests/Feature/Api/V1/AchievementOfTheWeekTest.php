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
        $this->addSoftcoreUnlock($this->user, $achievement1, $now);
        $this->addSoftcoreUnlock($user2, $achievement1, $now->copy()->subMinutes(5));
        $this->addHardcoreUnlock($user3, $achievement1, $now->copy()->addMinutes(5));

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
                'TotalPlayers' => 1, // only the hardcore unlock applies (would normally include people who have unlocked any AotW)
                'Unlocks' => [
                    [
                        'User' => $user3->User,
                        'RAPoints' => $user3->RAPoints,
                        'RASoftcorePoints' => $user3->RASoftcorePoints,
                        'HardcoreMode' => 1,
                    ],
                ],
                'UnlocksCount' => 1,
                'UnlocksHardcoreCount' => 1,
            ]);
    }

    public function testGetAchievementOfTheWeekStaticData(): void
    {
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        $now = Carbon::now();
        $this->addSoftcoreUnlock($this->user, $achievement, $now);
        $this->addSoftcoreUnlock($user2, $achievement, $now->copy()->subMinutes(5));
        $this->addHardcoreUnlock($user3, $achievement, $now->copy()->addMinutes(5));

        // fallback to static data until AotW data is populated
        $staticData = StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement->ID,
            'Event_AOTW_StartAt' => $now->clone()->subDay(),
        ]);

        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                ],
                'Console' => [
                    'ID' => $game->system_id,
                ],
                'ForumTopic' => [
                    'ID' => 1,
                ],
                'Game' => [
                    'ID' => $game->ID,
                ],
                'StartAt' => $staticData->Event_AOTW_StartAt->jsonSerialize(),
                'TotalPlayers' => 3,
                'Unlocks' => [
                    [
                        'User' => $user3->User,
                        'RAPoints' => $user3->RAPoints,
                        'RASoftcorePoints' => $user3->RASoftcorePoints,
                        'HardcoreMode' => 1,
                    ],
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'RASoftcorePoints' => $this->user->RASoftcorePoints,
                        'HardcoreMode' => 0,
                    ],
                    [
                        'User' => $user2->User,
                        'RAPoints' => $user2->RAPoints,
                        'RASoftcorePoints' => $user2->RASoftcorePoints,
                        'HardcoreMode' => 0,
                    ],
                ],
                'UnlocksCount' => 3,
                'UnlocksHardcoreCount' => 1,
            ]);
    }
}
