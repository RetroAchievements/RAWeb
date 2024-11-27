<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetFollowerUsersListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create(['User' => 'myUser']);
        $this->user = $user;
    }

    protected function apiUrl(string $method, array $params = []): string
    {
        $params = array_merge(['y' => $this->user->APIKey], $params);

        return sprintf('API/API_%s.php?%s', $method, http_build_query($params));
    }

    public function testGetFollowedUsersList(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $followerUser1 */
        $followerUser1 = User::factory()->create(['User' => 'myFollowerUser1', 'RichPresenceMsg' => 'Playing Friendship 1']);
        UserRelation::create([
            'user_id' => $followerUser1->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followerUser1->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser2 */
        $followerUser2 = User::factory()->create(['User' => 'myFollowerUser2', 'RichPresenceMsg' => 'Playing Friendship 2']);
        UserRelation::create([
            'user_id' => $followerUser2->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser3 */
        $followerUser3 = User::factory()->create(['User' => 'myFollowerUser3', 'RichPresenceMsg' => 'Playing Friendship 3']);
        UserRelation::create([
            'user_id' => $followerUser3->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser4 */
        $followerUser4 = User::factory()->create(['User' => 'myFollowerUser4', 'RichPresenceMsg' => 'Playing Friendship 4']);
        UserRelation::create([
            'user_id' => $followerUser4->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser5 */
        $followerUser5 = User::factory()->create(['User' => 'myFollowerUser5']);
        UserRelation::create([
            'user_id' => $followerUser5->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        $this->get($this->apiUrl('GetFollowerUsersList'))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Friend" => $followerUser1->User,
                        "Points" => $followerUser1->Points,
                        "PointsSoftcore" => $followerUser1->points_softcore,
                        "LastSeen" => $followerUser1->RichPresenceMsg,
                        "FollowingBack" => true,
                        "ID" => $followerUser1->ID,
                    ],
                    [
                        "Friend" => $followerUser2->User,
                        "Points" => $followerUser2->Points,
                        "PointsSoftcore" => $followerUser2->points_softcore,
                        "LastSeen" => $followerUser2->RichPresenceMsg,
                        "FollowingBack" => false,
                        "ID" => $followerUser2->ID,
                    ],
                    [
                        "Friend" => $followerUser3->User,
                        "Points" => $followerUser3->Points,
                        "PointsSoftcore" => $followerUser3->points_softcore,
                        "LastSeen" => $followerUser3->RichPresenceMsg,
                        "FollowingBack" => false,
                        "ID" => $followerUser3->ID,
                    ],
                    [
                        "Friend" => $followerUser4->User,
                        "Points" => $followerUser4->Points,
                        "PointsSoftcore" => $followerUser4->points_softcore,
                        "LastSeen" => $followerUser4->RichPresenceMsg,
                        "FollowingBack" => false,
                        "ID" => $followerUser4->ID,
                    ],
                    [
                        "Friend" => $followerUser5->User,
                        "Points" => $followerUser5->Points,
                        "PointsSoftcore" => $followerUser5->points_softcore,
                        "LastSeen" => 'Unknown', // No Rich Presence
                        "FollowingBack" => false,
                        "ID" => $followerUser5->ID,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetFollowerUsersList', ['o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followerUser4->User,
                            "Points" => $followerUser4->Points,
                            "PointsSoftcore" => $followerUser4->points_softcore,
                            "LastSeen" => $followerUser4->RichPresenceMsg,
                            "FollowingBack" => false,
                            "ID" => $followerUser4->ID,
                        ],
                        [
                            "Friend" => $followerUser5->User,
                            "Points" => $followerUser5->Points,
                            "PointsSoftcore" => $followerUser5->points_softcore,
                            "LastSeen" => 'Unknown', // No Rich Presence
                            "FollowingBack" => false,
                            "ID" => $followerUser5->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetFollowerUsersList', ['c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followerUser1->User,
                            "Points" => $followerUser1->Points,
                            "PointsSoftcore" => $followerUser1->points_softcore,
                            "LastSeen" => $followerUser1->RichPresenceMsg,
                            "FollowingBack" => true,
                            "ID" => $followerUser1->ID,
                        ],
                        [
                            "Friend" => $followerUser2->User,
                            "Points" => $followerUser2->Points,
                            "PointsSoftcore" => $followerUser2->points_softcore,
                            "LastSeen" => $followerUser2->RichPresenceMsg,
                            "FollowingBack" => false,
                            "ID" => $followerUser2->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetFollowerUsersList', ['o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followerUser2->User,
                            "Points" => $followerUser2->Points,
                            "PointsSoftcore" => $followerUser2->points_softcore,
                            "LastSeen" => $followerUser2->RichPresenceMsg,
                            "FollowingBack" => false,
                            "ID" => $followerUser2->ID,
                        ],
                        [
                            "Friend" => $followerUser3->User,
                            "Points" => $followerUser3->Points,
                            "PointsSoftcore" => $followerUser3->points_softcore,
                            "LastSeen" => $followerUser3->RichPresenceMsg,
                            "FollowingBack" => false,
                            "ID" => $followerUser3->ID,
                        ],
                    ],
                ]);
    }
}
