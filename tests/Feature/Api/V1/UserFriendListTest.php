<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserFriendListTest extends TestCase
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

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserFriendList', ['u' => '1']))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserWantToPlayListUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserFriendList', ['u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetUserWantToPlayList(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $followedUser */
        $followedUser = User::factory()->create(['User' => 'followedUser']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followingUser */
        $followingUser = User::factory()->create(['User' => 'followingUser']);
        UserRelation::create([
            'user_id' => $followingUser->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend1 */
        $friend1 = User::factory()->create(['User' => 'myFriend1', 'RichPresenceMsg' => 'Playing Friendship 1']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend1->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend1->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend2 */
        $friend2 = User::factory()->create(['User' => 'myFriend2', 'RichPresenceMsg' => 'Playing Friendship 2']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend2->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend2->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend3 */
        $friend3 = User::factory()->create(['User' => 'myFriend3', 'RichPresenceMsg' => 'Playing Friendship 3']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend3->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend3->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend4 */
        $friend4 = User::factory()->create(['User' => 'myFriend4', 'RichPresenceMsg' => 'Playing Friendship 4']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend4->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend4->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend5 */
        $friend5 = User::factory()->create(['User' => 'myFriend5']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend5->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend5->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        $this->get($this->apiUrl('GetUserFriendList', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Friend" => $friend1->User,
                        "Points" => $friend1->Points,
                        "PointsSoftcore" => $friend1->points_softcore,
                        "LastSeen" => $friend1->RichPresenceMsg,
                        "ID" => $friend1->ID,
                    ],
                    [
                        "Friend" => $friend2->User,
                        "Points" => $friend2->Points,
                        "PointsSoftcore" => $friend2->points_softcore,
                        "LastSeen" => $friend2->RichPresenceMsg,
                        "ID" => $friend2->ID,
                    ],
                    [
                        "Friend" => $friend3->User,
                        "Points" => $friend3->Points,
                        "PointsSoftcore" => $friend3->points_softcore,
                        "LastSeen" => $friend3->RichPresenceMsg,
                        "ID" => $friend3->ID,
                    ],
                    [
                        "Friend" => $friend4->User,
                        "Points" => $friend4->Points,
                        "PointsSoftcore" => $friend4->points_softcore,
                        "LastSeen" => $friend4->RichPresenceMsg,
                        "ID" => $friend4->ID,
                    ],
                    [
                        "Friend" => $friend5->User,
                        "Points" => $friend5->Points,
                        "PointsSoftcore" => $friend5->points_softcore,
                        "LastSeen" => 'Unknown', // No Rich Presence
                        "ID" => $friend5->ID,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserFriendList', ['u' => $this->user->User, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $friend4->User,
                            "Points" => $friend4->Points,
                            "PointsSoftcore" => $friend4->points_softcore,
                            "LastSeen" => $friend4->RichPresenceMsg,
                            "ID" => $friend4->ID,
                        ],
                        [
                            "Friend" => $friend5->User,
                            "Points" => $friend5->Points,
                            "PointsSoftcore" => $friend5->points_softcore,
                            "LastSeen" => 'Unknown', // No Rich Presence
                            "ID" => $friend5->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserFriendList', ['u' => $this->user->User, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $friend1->User,
                            "Points" => $friend1->Points,
                            "PointsSoftcore" => $friend1->points_softcore,
                            "LastSeen" => $friend1->RichPresenceMsg,
                            "ID" => $friend1->ID,
                        ],
                        [
                            "Friend" => $friend2->User,
                            "Points" => $friend2->Points,
                            "PointsSoftcore" => $friend2->points_softcore,
                            "LastSeen" => $friend2->RichPresenceMsg,
                            "ID" => $friend2->ID,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserFriendList', ['u' => $this->user->User, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Friend" => $friend2->User,
                            "Points" => $friend2->Points,
                            "PointsSoftcore" => $friend2->points_softcore,
                            "LastSeen" => $friend2->RichPresenceMsg,
                            "ID" => $friend2->ID,
                        ],
                        [
                            "Friend" => $friend3->User,
                            "Points" => $friend3->Points,
                            "PointsSoftcore" => $friend3->points_softcore,
                            "LastSeen" => $friend3->RichPresenceMsg,
                            "ID" => $friend3->ID,
                        ],
                    ],
                ]);

                $this->get($this->apiUrl('GetUserFriendList', ['u' => $followedUser->User]))
                    ->assertUnauthorized()
                    ->assertJson([]);

                $this->get($this->apiUrl('GetUserFriendList', ['u' => $followingUser->User]))
                    ->assertUnauthorized()
                    ->assertJson([]);
    }
}
