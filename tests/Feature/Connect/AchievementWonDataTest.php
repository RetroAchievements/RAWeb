<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\UserRelationStatus;
use App\Connect\Actions\GetAchievementUnlocksAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AchievementWonDataTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testCountIsClampedToUpperBound(): void
    {
        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $this->get($this->apiUrl('achievementwondata', [
            'a' => $achievement->id,
            'c' => GetAchievementUnlocksAction::MAX_COUNT + 1,
        ]))
            ->assertStatus(200)
            ->assertJsonFragment(['Count' => GetAchievementUnlocksAction::MAX_COUNT]);
    }

    public function testSoftDeletedUserIsExcludedFromRecentWinners(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        /** @var User $activeUser */
        $activeUser = User::factory()->create(['display_name' => 'ActiveUser']);
        /** @var User $deletedUser */
        $deletedUser = User::factory()->create(['display_name' => 'DeletedUser']);

        $this->addHardcoreUnlock($deletedUser, $achievement, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($activeUser, $achievement, Carbon::now()->subMinutes(5));

        $deletedUser->delete();

        $response = $this->get($this->apiUrl('achievementwondata', ['a' => $achievement->id]))
            ->assertStatus(200);

        $winners = $response->json('Response.RecentWinner');
        $this->assertCount(1, $winners);
        $this->assertEquals('ActiveUser', $winners[0]['User']);
    }

    public function testRecentWinners(): void
    {
        $userCount = 20;
        User::factory()->count($userCount)->create();

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

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
                $this->addCasualUnlock($user, $achievement1, $now);
            } elseif ($count % 4 != 1) { // 3,4,7,8,11,12,15,16,19
                $this->addHardcoreUnlock($user, $achievement1, $now);
            }

            // $count/3 users will have unlocked achievement2
            if ($count % 3 == 1) { // 1,4,7,10,13,16,19
                $this->addCasualUnlock($user, $achievement2, $now);
            }

            $user->display_name = $user->username;
            $user->save();

            // 1 and 13 will have only unlocked achievement2
            // 5,9,17 will not have unlocked either
        }

        // first achievement - 5 most recent
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement1->id, 'c' => 5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 5,
                'FriendsOnly' => false,
                'AchievementID' => $achievement1->id,
                'Response' => [
                    'NumEarned' => 14,
                    'GameID' => $game->id,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[19]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[19]->username . '.png'), 'RAPoints' => $users[19]->points_hardcore, 'DateAwarded' => $unlocks[19]],
                        ['User' => $users[18]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[18]->username . '.png'), 'RAPoints' => $users[18]->points_hardcore, 'DateAwarded' => $unlocks[18]],
                        ['User' => $users[16]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[16]->username . '.png'), 'RAPoints' => $users[16]->points_hardcore, 'DateAwarded' => $unlocks[16]],
                        ['User' => $users[15]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[15]->username . '.png'), 'RAPoints' => $users[15]->points_hardcore, 'DateAwarded' => $unlocks[15]],
                        ['User' => $users[14]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[14]->username . '.png'), 'RAPoints' => $users[14]->points_hardcore, 'DateAwarded' => $unlocks[14]],
                    ],
                ],
            ]);

        // first achievement - offset and ask for more than available
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement1->id, 'o' => 12, 'c' => 6]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Offset' => 12,
                'Count' => 6,
                'FriendsOnly' => false,
                'AchievementID' => $achievement1->id,
                'Response' => [
                    'NumEarned' => 14,
                    'GameID' => $game->id,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[3]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[3]->username . '.png'), 'RAPoints' => $users[3]->points_hardcore, 'DateAwarded' => $unlocks[3]],
                        ['User' => $users[2]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[2]->username . '.png'), 'RAPoints' => $users[2]->points_hardcore, 'DateAwarded' => $unlocks[2]],
                    ],
                ],
            ]);

        // other achievement - different earn rate/winners
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement2->id, 'o' => 3, 'c' => 4]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Offset' => 3,
                'Count' => 4,
                'FriendsOnly' => false,
                'AchievementID' => $achievement2->id,
                'Response' => [
                    'NumEarned' => 7,
                    'GameID' => $game->id,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[10]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[10]->username . '.png'), 'RAPoints' => $users[10]->points_hardcore, 'DateAwarded' => $unlocks[10]],
                        ['User' => $users[7]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[7]->username . '.png'), 'RAPoints' => $users[7]->points_hardcore, 'DateAwarded' => $unlocks[7]],
                        ['User' => $users[4]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[4]->username . '.png'), 'RAPoints' => $users[4]->points_hardcore, 'DateAwarded' => $unlocks[4]],
                        ['User' => $users[1]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[1]->username . '.png'), 'RAPoints' => $users[1]->points_hardcore, 'DateAwarded' => $unlocks[1]],
                    ],
                ],
            ]);

        // third achievement - no unlocks
        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement3->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 10,
                'FriendsOnly' => false,
                'AchievementID' => $achievement3->id,
                'Response' => [
                    'NumEarned' => 0,
                    'GameID' => $game->id,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [],
                ],
            ]);

        // non-existent achievement
        $this->get($this->apiUrl('achievementwondata', ['a' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown achievement.',
            ]);

        // second achievement - friends only
        $this->assertEquals($this->user->id, $users[1]->id); /* logic assumes that first user is making API call */

        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $users[10]->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $users[4]->id,
            'status' => UserRelationStatus::Following,
        ]);

        $this->get($this->apiUrl('achievementwondata', ['a' => $achievement2->id, 'f' => 1]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Offset' => 0,
                'Count' => 10,
                'FriendsOnly' => true,
                'AchievementID' => $achievement2->id,
                'Response' => [
                    'NumEarned' => 7,
                    'GameID' => $game->id,
                    'TotalPlayers' => 16,
                    'RecentWinner' => [
                        ['User' => $users[10]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[10]->username . '.png'), 'RAPoints' => $users[10]->points_hardcore, 'DateAwarded' => $unlocks[10]],
                        ['User' => $users[4]->display_name, 'AvatarUrl' => media_asset('UserPic/' . $users[4]->username . '.png'), 'RAPoints' => $users[4]->points_hardcore, 'DateAwarded' => $unlocks[4]],
                        // $users[1] is not their own friend, so won't be in the list
                    ],
                ],
            ]);
    }
}
