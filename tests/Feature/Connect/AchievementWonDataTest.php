<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AchievementWonDataTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testRecentWinners(): void
    {
        $userCount = 20;
        User::factory()->count($userCount)->create();

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

        $users = [];
        $unlocks = [];
        $now = Carbon::now()->clone()->subDays(2);

        for ($count = 1; $count < $userCount; $count++) {
            $user = User::findOrFail($count);
            $users[$count] = $user;

            $now = $now->addMinutes(5);
            $unlocks[$count] = $now->timestamp;

            // $count/4 users will NOT have unlocked achievement.
            // $count/4 users will only unlock in softcore
            if ($count % 4 == 2) { // 2,6,10,14,18
                $this->addSoftcoreUnlock($user, $achievement1, $now);
            } elseif ($count % 4 != 1) { // 3,4,7,8,11,12,15,16,19
                $this->addHardcoreUnlock($user, $achievement1, $now);
            }

            // $count/3 users will have unlocked achievement2
            if ($count % 3 == 1) { // 1,4,7,10,13,16,19
                $this->addSoftcoreUnlock($user, $achievement2, $now);
            }

            // 1 and 13 will have only unlocked achievement2
            // 5,9,17 will not have unlocked either
        }

        // first achievement - 5 most recent
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement1->ID, 'c' => 5]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 5,
                'FriendsOnly' => false,
                'AchievementID' => $achievement1->ID,
                'Response' => [
                    'NumEarned' => 14,
                    'GameID' => $game->ID,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[19]->User, 'RAPoints' => $users[19]->RAPoints, 'DateAwarded' => $unlocks[19]],
                        ['User' => $users[18]->User, 'RAPoints' => $users[18]->RAPoints, 'DateAwarded' => $unlocks[18]],
                        ['User' => $users[16]->User, 'RAPoints' => $users[16]->RAPoints, 'DateAwarded' => $unlocks[16]],
                        ['User' => $users[15]->User, 'RAPoints' => $users[15]->RAPoints, 'DateAwarded' => $unlocks[15]],
                        ['User' => $users[14]->User, 'RAPoints' => $users[14]->RAPoints, 'DateAwarded' => $unlocks[14]],
                    ],
                ],
            ]);

        // first achievement - offset and ask for more than available
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement1->ID, 'o' => 12, 'c' => 6]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 12,
                'Count' => 6,
                'FriendsOnly' => false,
                'AchievementID' => $achievement1->ID,
                'Response' => [
                    'NumEarned' => 14,
                    'GameID' => $game->ID,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[3]->User, 'RAPoints' => $users[3]->RAPoints, 'DateAwarded' => $unlocks[3]],
                        ['User' => $users[2]->User, 'RAPoints' => $users[2]->RAPoints, 'DateAwarded' => $unlocks[2]],
                    ],
                ],
            ]);

        // other achievement - different earn rate/winners
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement2->ID, 'o' => 3, 'c' => 4]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 3,
                'Count' => 4,
                'FriendsOnly' => false,
                'AchievementID' => $achievement2->ID,
                'Response' => [
                    'NumEarned' => 7,
                    'GameID' => $game->ID,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[10]->User, 'RAPoints' => $users[10]->RAPoints, 'DateAwarded' => $unlocks[10]],
                        ['User' => $users[7]->User, 'RAPoints' => $users[7]->RAPoints, 'DateAwarded' => $unlocks[7]],
                        ['User' => $users[4]->User, 'RAPoints' => $users[4]->RAPoints, 'DateAwarded' => $unlocks[4]],
                        ['User' => $users[1]->User, 'RAPoints' => $users[1]->RAPoints, 'DateAwarded' => $unlocks[1]],
                    ],
                ],
            ]);

        // third achievement - no unlocks
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement3->ID]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 10,
                'FriendsOnly' => false,
                'AchievementID' => $achievement3->ID,
                'Response' => [
                    'NumEarned' => 0,
                    'GameID' => $game->ID,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [],
                ],
            ]);

        // non-existant achievement
        $this->get($this->apiUrl('achievementwondata', ['a' => 999999]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 10,
                'FriendsOnly' => false,
                'AchievementID' => 999999,
                'Response' => [
                    'NumEarned' => 0,
                    'GameID' => 0,
                    'TotalPlayers' => 0,
                    'RecentWinner' => [],
                ],
            ]);

        // second achievement - friends only
        $this->assertEquals($this->user->ID, $users[1]->ID); /* logic assumes that first user is making API call */
        // TODO: Use UserRelation model
        legacyDbStatement("INSERT INTO Friends (User, Friend, Friendship) VALUES (:user, :friend, 1)",
            ['user' => $this->user->User, 'friend' => $users[10]->User]);
        legacyDbStatement("INSERT INTO Friends (User, Friend, Friendship) VALUES (:user, :friend, 1)",
            ['user' => $this->user->User, 'friend' => $users[4]->User]);
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement2->ID, 'f' => 1]))
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 10,
                'FriendsOnly' => true,
                'AchievementID' => $achievement2->ID,
                'Response' => [
                    'NumEarned' => 7,
                    'GameID' => $game->ID,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[10]->User, 'RAPoints' => $users[10]->RAPoints, 'DateAwarded' => $unlocks[10]],
                        ['User' => $users[4]->User, 'RAPoints' => $users[4]->RAPoints, 'DateAwarded' => $unlocks[4]],
                        // $users[1] is not their own friend, so won't be in the list
                    ],
                ],
            ]);
    }
}
