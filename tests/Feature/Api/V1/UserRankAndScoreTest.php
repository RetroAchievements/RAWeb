<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRankAndScoreTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserCompletionProgress'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserRankAndScoreUnknownUser(): void
    {
        $this->user->points_hardcore = 600; // make sure enough points to be ranked
        $this->user->save();

        $this->get($this->apiUrl('GetUserRankAndScore', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([
                'Score' => 0,
                'SoftcoreScore' => 0,
                'Rank' => null,
                'TotalRanked' => 1,
            ]);
    }

    public function testGetUserRankAndScoreByName(): void
    {
        $this->user->points_hardcore = 600; // make sure enough points to be ranked
        $this->user->save();

        /** @var User $user */
        $user = User::factory()->create([
            'points' => 371,
            'points_hardcore' => 25842,
        ]);

        $this->get($this->apiUrl('GetUserRankAndScore', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                'Score' => $user->points_hardcore,
                'SoftcoreScore' => $user->points,
                'Rank' => 1,
                'TotalRanked' => 2, // $this->user and $user
            ]);
    }

    public function testGetUserRankAndScoreByUlid(): void
    {
        $this->user->points_hardcore = 600; // make sure enough points to be ranked
        $this->user->save();

        /** @var User $user */
        $user = User::factory()->create([
            'points' => 371,
            'points_hardcore' => 25842,
        ]);

        $this->get($this->apiUrl('GetUserRankAndScore', ['u' => $user->ulid]))
            ->assertSuccessful()
            ->assertJson([
                'Score' => $user->points_hardcore,
                'SoftcoreScore' => $user->points,
                'Rank' => 1,
                'TotalRanked' => 2, // $this->user and $user
            ]);
    }
}
