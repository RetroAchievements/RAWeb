<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetUsersIFollowTest extends TestCase
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

        $api = "GetUsersIFollow";

        /** @var User $followedUser1 */
        $followedUser1 = User::factory()->create(['User' => 'myFollowedUser1']);
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
        $followedUser2 = User::factory()->create(['User' => 'myFollowedUser2']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser2->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser3 */
        $followedUser3 = User::factory()->create(['User' => 'myFollowedUser3']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser3->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followedUser4 */
        $followedUser4 = User::factory()->create(['User' => 'myFollowedUser4']);
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

        $this->get($this->apiUrl($api))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "User" => $followedUser1->display_name,
                        "ULID" => $followedUser1->ulid,
                        "Points" => $followedUser1->Points,
                        "PointsSoftcore" => $followedUser1->points_softcore,
                        "IsFollowingMe" => true,
                    ],
                    [
                        "User" => $followedUser2->display_name,
                        "ULID" => $followedUser2->ulid,
                        "Points" => $followedUser2->Points,
                        "PointsSoftcore" => $followedUser2->points_softcore,
                        "IsFollowingMe" => false,
                    ],
                    [
                        "User" => $followedUser3->display_name,
                        "ULID" => $followedUser3->ulid,
                        "Points" => $followedUser3->Points,
                        "PointsSoftcore" => $followedUser3->points_softcore,
                        "IsFollowingMe" => false,
                    ],
                    [
                        "User" => $followedUser4->display_name,
                        "ULID" => $followedUser4->ulid,
                        "Points" => $followedUser4->Points,
                        "PointsSoftcore" => $followedUser4->points_softcore,
                        "IsFollowingMe" => false,
                    ],
                    [
                        "User" => $followedUser5->display_name,
                        "ULID" => $followedUser5->ulid,
                        "Points" => $followedUser5->Points,
                        "PointsSoftcore" => $followedUser5->points_softcore,
                        "IsFollowingMe" => false,
                    ],
                ],
            ]);

            $this->get($this->apiUrl($api, ['o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "User" => $followedUser4->display_name,
                            "ULID" => $followedUser4->ulid,
                            "Points" => $followedUser4->Points,
                            "PointsSoftcore" => $followedUser4->points_softcore,
                            "IsFollowingMe" => false,
                        ],
                        [
                            "User" => $followedUser5->display_name,
                            "ULID" => $followedUser5->ulid,
                            "Points" => $followedUser5->Points,
                            "PointsSoftcore" => $followedUser5->points_softcore,
                            "IsFollowingMe" => false,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl($api, ['c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "User" => $followedUser1->display_name,
                            "ULID" => $followedUser1->ulid,
                            "Points" => $followedUser1->Points,
                            "PointsSoftcore" => $followedUser1->points_softcore,
                            "IsFollowingMe" => true,
                        ],
                        [
                            "User" => $followedUser2->display_name,
                            "ULID" => $followedUser2->ulid,
                            "Points" => $followedUser2->Points,
                            "PointsSoftcore" => $followedUser2->points_softcore,
                            "IsFollowingMe" => false,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl($api, ['c' => 750]))
                ->assertUnprocessable()
                ->assertJson([
                    'message' => 'The c must not be greater than 500.',
                    'errors' => [
                        "c" => [
                            'The c must not be greater than 500.',
                        ],
                    ],
                ]);

            $this->get($this->apiUrl($api, ['o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "User" => $followedUser2->display_name,
                            "ULID" => $followedUser2->ulid,
                            "Points" => $followedUser2->Points,
                            "PointsSoftcore" => $followedUser2->points_softcore,
                            "IsFollowingMe" => false,
                        ],
                        [
                            "User" => $followedUser3->display_name,
                            "ULID" => $followedUser3->ulid,
                            "Points" => $followedUser3->Points,
                            "PointsSoftcore" => $followedUser3->points_softcore,
                            "IsFollowingMe" => false,
                        ],
                    ],
                ]);
    }
}
