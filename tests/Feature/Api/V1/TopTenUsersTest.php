<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopTenUsersTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetTopTenUsers(): void
    {
        $this->user->Untracked = true; // prevent random points from appearing in list
        $this->user->save();

        /** @var User $user1 */
        $user1 = User::factory()->create(['points' => 25842, 'points_weighted' => 34584]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['points' => 3847, 'points_weighted' => 5378]);
        /** @var User $user3 */
        $user3 = User::factory()->create(['points' => 25840, 'points_weighted' => 46980]);
        User::factory()->create(['points' => 0, 'points_weighted' => 0]);
        User::factory()->create(['points' => 172, 'points_weighted' => 223]);
        /** @var User $user6 */
        $user6 = User::factory()->create(['points' => 85736, 'points_weighted' => 102332]);
        /** @var User $user7 */
        $user7 = User::factory()->create(['points' => 64633, 'points_weighted' => 94838]);
        /** @var User $user8 */
        $user8 = User::factory()->create(['points' => 44337, 'points_weighted' => 75347]);
        /** @var User $user9 */
        $user9 = User::factory()->create(['points' => 574, 'points_weighted' => 851]);
        /** @var User $user10 */
        $user10 = User::factory()->create(['points' => 54367, 'points_weighted' => 74373]);
        /** @var User $user11 */
        $user11 = User::factory()->create(['points' => 76289, 'points_weighted' => 95871]);
        /** @var User $user12 */
        $user12 = User::factory()->create(['points' => 75732, 'points_weighted' => 97553]);

        $this->get($this->apiUrl('GetTopTenUsers'))
            ->assertSuccessful()
            ->assertJson([
                ['1' => $user6->username, '2' => $user6->points, '3' => $user6->points_weighted, '4' => $user6->ulid],
                ['1' => $user11->username, '2' => $user11->points, '3' => $user11->points_weighted, '4' => $user11->ulid],
                ['1' => $user12->username, '2' => $user12->points, '3' => $user12->points_weighted, '4' => $user12->ulid],
                ['1' => $user7->username, '2' => $user7->points, '3' => $user7->points_weighted, '4' => $user7->ulid],
                ['1' => $user10->username, '2' => $user10->points, '3' => $user10->points_weighted, '4' => $user10->ulid],
                ['1' => $user8->username, '2' => $user8->points, '3' => $user8->points_weighted, '4' => $user8->ulid],
                ['1' => $user1->username, '2' => $user1->points, '3' => $user1->points_weighted, '4' => $user1->ulid],
                ['1' => $user3->username, '2' => $user3->points, '3' => $user3->points_weighted, '4' => $user3->ulid],
                ['1' => $user2->username, '2' => $user2->points, '3' => $user2->points_weighted, '4' => $user2->ulid],
                ['1' => $user9->username, '2' => $user9->points, '3' => $user9->points_weighted, '4' => $user9->ulid],
            ]);
    }
}
