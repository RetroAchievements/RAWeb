<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\System;
use App\Site\Models\StaticData;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AchievementOfTheWeekTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetAchievementOfTheWeekEmptyResponse(): void
    {
        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null], 'StartAt' => null]);
    }

    public function testGetAchievementOfTheWeek(): void
    {
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        $now = Carbon::now();
        /** @var PlayerAchievementLegacy $unlock */
        $unlock = PlayerAchievementLegacy::factory()->create(['AchievementID' => $achievement->ID, 'User' => $this->user->User, 'Date' => $now]);
        /** @var PlayerAchievementLegacy $unlock2 */
        $unlock2 = PlayerAchievementLegacy::factory()->create(['AchievementID' => $achievement->ID, 'User' => $user2->User, 'Date' => $now->copy()->subMinutes(5)]);
        /** @var PlayerAchievementLegacy $unlock3 */
        $unlock3 = PlayerAchievementLegacy::factory()->create(['AchievementID' => $achievement->ID, 'User' => $user3->User, 'Date' => $now->copy()->addMinutes(5)]);

        $staticData = StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement->ID,
            'Event_AOTW_StartAt' => Carbon::now()->subDay(),
        ]);

        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                ],
                'Console' => [
                    'ID' => $system->ID,
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
                        'HardcoreMode' => $unlock3->HardcoreMode,
                    ],
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'HardcoreMode' => $unlock->HardcoreMode,
                    ],
                    [
                        'User' => $user2->User,
                        'RAPoints' => $user2->RAPoints,
                        'HardcoreMode' => $unlock2->HardcoreMode,
                    ],
                ],
                'UnlocksCount' => 3,
            ]);
    }
}
