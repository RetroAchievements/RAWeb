<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetUsersFollowingMeTest extends TestCase
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

    public function testGetFollowerUsersList(): void
    {
        Carbon::setTestNow(Carbon::now());

        $api = "GetUsersFollowingMe";

        /** @var User $followerUser1 */
        $followerUser1 = User::factory()->create(['User' => 'myFollowerUser1']);
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
        $followerUser2 = User::factory()->create(['User' => 'myFollowerUser2']);
        UserRelation::create([
            'user_id' => $followerUser2->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser3 */
        $followerUser3 = User::factory()->create(['User' => 'myFollowerUser3']);
        UserRelation::create([
            'user_id' => $followerUser3->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followerUser4 */
        $followerUser4 = User::factory()->create(['User' => 'myFollowerUser4']);
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

        $this->get($this->apiUrl($api))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "User" => $followerUser1->display_name,
                        "Points" => $followerUser1->Points,
                        "PointsSoftcore" => $followerUser1->points_softcore,
                        "AmIFollowing" => true,
                    ],
                    [
                        "User" => $followerUser2->display_name,
                        "Points" => $followerUser2->Points,
                        "PointsSoftcore" => $followerUser2->points_softcore,
                        "AmIFollowing" => false,
                    ],
                    [
                        "User" => $followerUser3->display_name,
                        "Points" => $followerUser3->Points,
                        "PointsSoftcore" => $followerUser3->points_softcore,
                        "AmIFollowing" => false,
                    ],
                    [
                        "User" => $followerUser4->display_name,
                        "Points" => $followerUser4->Points,
                        "PointsSoftcore" => $followerUser4->points_softcore,
                        "AmIFollowing" => false,
                    ],
                    [
                        "User" => $followerUser5->display_name,
                        "Points" => $followerUser5->Points,
                        "PointsSoftcore" => $followerUser5->points_softcore,
                        "AmIFollowing" => false,
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
                            "User" => $followerUser4->display_name,
                            "Points" => $followerUser4->Points,
                            "PointsSoftcore" => $followerUser4->points_softcore,
                            "AmIFollowing" => false,
                        ],
                        [
                            "User" => $followerUser5->display_name,
                            "Points" => $followerUser5->Points,
                            "PointsSoftcore" => $followerUser5->points_softcore,
                            "AmIFollowing" => false,
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
                            "User" => $followerUser1->display_name,
                            "Points" => $followerUser1->Points,
                            "PointsSoftcore" => $followerUser1->points_softcore,
                            "AmIFollowing" => true,
                        ],
                        [
                            "User" => $followerUser2->display_name,
                            "Points" => $followerUser2->Points,
                            "PointsSoftcore" => $followerUser2->points_softcore,
                            "AmIFollowing" => false,
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
                            "User" => $followerUser2->display_name,
                            "Points" => $followerUser2->Points,
                            "PointsSoftcore" => $followerUser2->points_softcore,
                            "AmIFollowing" => false,
                        ],
                        [
                            "User" => $followerUser3->display_name,
                            "Points" => $followerUser3->Points,
                            "PointsSoftcore" => $followerUser3->points_softcore,
                            "AmIFollowing" => false,
                        ],
                    ],
                ]);
    }
}
