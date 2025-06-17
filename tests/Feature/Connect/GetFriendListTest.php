<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\UserRelationship;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetFriendListTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testGetFriendList(): void
    {
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $date2 = Carbon::parse('2024-03-05 17:53:03');
        $date3 = Carbon::parse('2024-05-27 13:36:42');

        /** @var Game $game1 */
        $game1 = $this->seedGame();
        /** @var Game $game2 */
        $game2 = $this->seedGame();

        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();
        /** @var User $user5 */
        $user5 = User::factory()->create();
        /** @var User $user6 */
        $user6 = User::factory()->create();

        // no followed users
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);

        // user2 is playing game1
        $user2->LastActivityID = 2;
        $user2->LastGameID = $game1->ID;
        $user2->RichPresenceMsg = "Running through a forest";
        $user2->RichPresenceMsgDate = $date1;
        $user2->save();

        // user is following user2 (legacy RP - no session)
        changeFriendStatus($this->user, $user2, UserRelationship::Following);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'RAPoints' => $user2->points,
                        'LastSeen' => $user2->RichPresenceMsg,
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->Title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                ],
            ]);

        // user3 is playing game2
        $user3->LastActivityID = 3;
        $user3->LastGameID = $game2->ID;
        $user3->RichPresenceMsg = "Killing everything";
        $user3->RichPresenceMsgDate = $date2->clone()->subMinutes(45);
        $user3->save();

        PlayerSession::factory()->create([
            'user_id' => $user3->ID,
            'game_id' => $game2->ID,
            'rich_presence' => "Titles",
            'rich_presence_updated_at' => $date2,
        ]);

        // user4 is playing game2
        $user4->LastActivityID = 4;
        $user4->LastGameID = $game2->ID;
        $user4->RichPresenceMsg = "Killing everything";
        $user4->RichPresenceMsgDate = $date3;
        $user4->setAttribute('Permissions', Permissions::Banned);
        $user4->save();

        // user is following user3 (RP from session)
        changeFriendStatus($this->user, $user3, UserRelationship::Following);

        // user is following user4 (banned - should not be returned)
        changeFriendStatus($this->user, $user4, UserRelationship::Following);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user3->display_name,
                        'AvatarUrl' => $user3->avatar_url,
                        'RAPoints' => $user3->points,
                        'LastSeen' => "Titles",
                        'LastSeenTime' => $date2->unix(),
                        'LastGameId' => $game2->id,
                        'LastGameTitle' => $game2->Title,
                        'LastGameIconUrl' => $game2->badge_url,
                    ],
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'RAPoints' => $user2->points,
                        'LastSeen' => $user2->RichPresenceMsg,
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->Title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                ],
            ]);

        // user5 is playing game2
        $user5->LastActivityID = 5;
        $user5->LastGameID = $game2->ID;
        $user5->RichPresenceMsg = "Killing everything";
        $user5->RichPresenceMsgDate = $date3;
        $user5->save();

        // user5 is following user (inverse relationship)
        changeFriendStatus($user5, $this->user, UserRelationship::Following);

        // user6 has no activity
        $user6->LastLogin = $date3;
        $user6->save();

        // user is following user6 (legacy RP - no session)
        changeFriendStatus($this->user, $user6, UserRelationship::Following);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user6->display_name,
                        'AvatarUrl' => $user6->avatar_url,
                        'RAPoints' => $user6->points,
                        'LastSeen' => 'Unknown',
                        'LastSeenTime' => $date3->unix(),
                        'LastGameId' => null,
                        'LastGameTitle' => null,
                        'LastGameIconUrl' => null,
                    ],
                    [
                        'Friend' => $user3->display_name,
                        'AvatarUrl' => $user3->avatar_url,
                        'RAPoints' => $user3->points,
                        'LastSeen' => "Titles",
                        'LastSeenTime' => $date2->unix(),
                        'LastGameId' => $game2->id,
                        'LastGameTitle' => $game2->Title,
                        'LastGameIconUrl' => $game2->badge_url,
                    ],
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'RAPoints' => $user2->points,
                        'LastSeen' => $user2->RichPresenceMsg,
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->Title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                ],
            ]);

        // user6 is playing game 2
        $user6->LastActivityID = 6;
        $user6->LastGameID = $game2->ID;
        $user6->RichPresenceMsg = "Killing everything";
        $user6->RichPresenceMsgDate = $date3;
        $user6->save();

        // user has stopped following user2
        changeFriendStatus($this->user, $user2, UserRelationship::NotFollowing);

        // user has blocked user3
        changeFriendStatus($this->user, $user3, UserRelationship::NotFollowing);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user6->display_name,
                        'AvatarUrl' => $user6->avatar_url,
                        'RAPoints' => $user6->points,
                        'LastSeen' => $user6->RichPresenceMsg,
                        'LastSeenTime' => $date3->unix(),
                        'LastGameId' => $game2->id,
                        'LastGameTitle' => $game2->Title,
                        'LastGameIconUrl' => $game2->badge_url,
                    ],
                ],
            ]);
    }
}
