<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\StaticData;
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

    public function testGetAchievementOfTheWeek(): void
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
                        'HardcoreMode' => 1,
                    ],
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'HardcoreMode' => 0,
                    ],
                    [
                        'User' => $user2->User,
                        'RAPoints' => $user2->RAPoints,
                        'HardcoreMode' => 0,
                    ],
                ],
                'UnlocksCount' => 3,
                'UnlocksHardcoreCount' => 1,
            ]);
    }
}
