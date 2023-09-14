<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivityLegacy;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class StartSessionTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testStartSession(): void
    {
        $now = Carbon::create(2020, 3, 4, 16, 40, 13); // 4:40:13pm 4 Mar 2020
        Carbon::setTestNow($now);

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $unlock2Date = $now->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);
        $unlock3Date = $now->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        // ----------------------------
        // game with unlocks
        $this->get($this->apiUrl('startsession', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->ID,
                        'When' => $unlock1Date->timestamp,
                    ],
                    [
                        'ID' => $achievement2->ID,
                        'When' => $unlock2Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $achievement3->ID,
                        'When' => $unlock3Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        /** @var UserActivityLegacy $activity */
        $activity = UserActivityLegacy::latest()->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ActivityType::StartedPlaying, $activity->activitytype);
        $this->assertEquals($game->ID, $activity->data);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals("Playing " . $game->Title, $user1->RichPresenceMsg);

        // ----------------------------
        // non-existant game
        $this->get($this->apiUrl('startsession', ['g' => 999999]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown game',
            ]);

        // ----------------------------
        // game with no unlocks
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);

        $this->get($this->apiUrl('startsession', ['g' => $game2->ID]))
            ->assertExactJson([
                'Success' => true,
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        $activity = UserActivityLegacy::latest()->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ActivityType::StartedPlaying, $activity->activitytype);
        $this->assertEquals($game2->ID, $activity->data);

        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game2->ID, $user1->LastGameID);
        $this->assertEquals("Playing " . $game2->Title, $user1->RichPresenceMsg);
    }
}
