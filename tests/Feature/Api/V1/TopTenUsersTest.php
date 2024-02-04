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
        $user1 = User::factory()->create(['RAPoints' => 25842, 'TrueRAPoints' => 34584]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['RAPoints' => 3847, 'TrueRAPoints' => 5378]);
        /** @var User $user3 */
        $user3 = User::factory()->create(['RAPoints' => 25840, 'TrueRAPoints' => 46980]);
        User::factory()->create(['RAPoints' => 0, 'TrueRAPoints' => 0]);
        User::factory()->create(['RAPoints' => 172, 'TrueRAPoints' => 223]);
        /** @var User $user6 */
        $user6 = User::factory()->create(['RAPoints' => 85736, 'TrueRAPoints' => 102332]);
        /** @var User $user7 */
        $user7 = User::factory()->create(['RAPoints' => 64633, 'TrueRAPoints' => 94838]);
        /** @var User $user8 */
        $user8 = User::factory()->create(['RAPoints' => 44337, 'TrueRAPoints' => 75347]);
        /** @var User $user9 */
        $user9 = User::factory()->create(['RAPoints' => 574, 'TrueRAPoints' => 851]);
        /** @var User $user10 */
        $user10 = User::factory()->create(['RAPoints' => 54367, 'TrueRAPoints' => 74373]);
        /** @var User $user11 */
        $user11 = User::factory()->create(['RAPoints' => 76289, 'TrueRAPoints' => 95871]);
        /** @var User $user12 */
        $user12 = User::factory()->create(['RAPoints' => 75732, 'TrueRAPoints' => 97553]);

        $this->get($this->apiUrl('GetTopTenUsers'))
            ->assertSuccessful()
            ->assertJson([
                ['1' => $user6->User, '2' => $user6->RAPoints, '3' => $user6->TrueRAPoints],
                ['1' => $user11->User, '2' => $user11->RAPoints, '3' => $user11->TrueRAPoints],
                ['1' => $user12->User, '2' => $user12->RAPoints, '3' => $user12->TrueRAPoints],
                ['1' => $user7->User, '2' => $user7->RAPoints, '3' => $user7->TrueRAPoints],
                ['1' => $user10->User, '2' => $user10->RAPoints, '3' => $user10->TrueRAPoints],
                ['1' => $user8->User, '2' => $user8->RAPoints, '3' => $user8->TrueRAPoints],
                ['1' => $user1->User, '2' => $user1->RAPoints, '3' => $user1->TrueRAPoints],
                ['1' => $user3->User, '2' => $user3->RAPoints, '3' => $user3->TrueRAPoints],
                ['1' => $user2->User, '2' => $user2->RAPoints, '3' => $user2->TrueRAPoints],
                ['1' => $user9->User, '2' => $user9->RAPoints, '3' => $user9->TrueRAPoints],
            ]);
    }
}
