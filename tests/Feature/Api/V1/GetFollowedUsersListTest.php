<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetFollowedUsersListTest extends TestCase
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

        /** @var User $followedUser1 */
        $followedUser1 = User::factory()->create(['User' => 'myFollowedUser1', 'RichPresenceMsg' => 'Playing Friendship 1']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser1->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $followedUser1->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser2 */
        $followedUser2 = User::factory()->create(['User' => 'myFollowedUser2', 'RichPresenceMsg' => 'Playing Friendship 2']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser2->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser3 */
        $followedUser3 = User::factory()->create(['User' => 'myFollowedUser3', 'RichPresenceMsg' => 'Playing Friendship 3']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser3->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser4 */
        $followedUser4 = User::factory()->create(['User' => 'myFollowedUser4', 'RichPresenceMsg' => 'Playing Friendship 4']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser4->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser5 */
        $followedUser5 = User::factory()->create(['User' => 'myFollowedUser5']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser5->id,
            'Friendship' => UserRelationship::Following,
        ]);

        $this->get($this->apiUrl('GetFollowedUsersList'))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Friend" => $followedUser1->User,
                        "Points" => $followedUser1->Points,
                        "PointsSoftcore" => $followedUser1->points_softcore,
                        "LastSeen" => $followedUser1->RichPresenceMsg,
                        "FollowsBack" => true,
                        "ID" => $followedUser1->ID,
                    ],
                    [
                        "Friend" => $followedUser2->User,
                        "Points" => $followedUser2->Points,
                        "PointsSoftcore" => $followedUser2->points_softcore,
                        "LastSeen" => $followedUser2->RichPresenceMsg,
                        "FollowsBack" => false,
                        "ID" => $followedUser2->ID,
                    ],
                    [
                        "Friend" => $followedUser3->User,
                        "Points" => $followedUser3->Points,
                        "PointsSoftcore" => $followedUser3->points_softcore,
                        "LastSeen" => $followedUser3->RichPresenceMsg,
                        "FollowsBack" => false,
                        "ID" => $followedUser3->ID,
                    ],
                    [
                        "Friend" => $followedUser4->User,
                        "Points" => $followedUser4->Points,
                        "PointsSoftcore" => $followedUser4->points_softcore,
                        "LastSeen" => $followedUser4->RichPresenceMsg,
                        "FollowsBack" => false,
                        "ID" => $followedUser4->ID,
                    ],
                    [
                        "Friend" => $followedUser5->User,
                        "Points" => $followedUser5->Points,
                        "PointsSoftcore" => $followedUser5->points_softcore,
                        "LastSeen" => 'Unknown', // No Rich Presence
                        "FollowsBack" => false,
                        "ID" => $followedUser5->ID,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetFollowedUsersList', ['o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followedUser4->User,
                            "Points" => $followedUser4->Points,
                            "PointsSoftcore" => $followedUser4->points_softcore,
                            "LastSeen" => $followedUser4->RichPresenceMsg,
                            "FollowsBack" => false,
                            "ID" => $followedUser4->ID,
                        ],
                        [
                            "Friend" => $followedUser5->User,
                            "Points" => $followedUser5->Points,
                            "PointsSoftcore" => $followedUser5->points_softcore,
                            "LastSeen" => 'Unknown', // No Rich Presence
                            "FollowsBack" => false,
                            "ID" => $followedUser5->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetFollowedUsersList', ['c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followedUser1->User,
                            "Points" => $followedUser1->Points,
                            "PointsSoftcore" => $followedUser1->points_softcore,
                            "LastSeen" => $followedUser1->RichPresenceMsg,
                            "FollowsBack" => true,
                            "ID" => $followedUser1->ID,
                        ],
                        [
                            "Friend" => $followedUser2->User,
                            "Points" => $followedUser2->Points,
                            "PointsSoftcore" => $followedUser2->points_softcore,
                            "LastSeen" => $followedUser2->RichPresenceMsg,
                            "FollowsBack" => false,
                            "ID" => $followedUser2->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetFollowedUsersList', ['o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $followedUser2->User,
                            "Points" => $followedUser2->Points,
                            "PointsSoftcore" => $followedUser2->points_softcore,
                            "LastSeen" => $followedUser2->RichPresenceMsg,
                            "FollowsBack" => false,
                            "ID" => $followedUser2->ID,
                        ],
                        [
                            "Friend" => $followedUser3->User,
                            "Points" => $followedUser3->Points,
                            "PointsSoftcore" => $followedUser3->points_softcore,
                            "LastSeen" => $followedUser3->RichPresenceMsg,
                            "FollowsBack" => false,
                            "ID" => $followedUser3->ID,
                        ],
                    ],
                ]);
    }
}
