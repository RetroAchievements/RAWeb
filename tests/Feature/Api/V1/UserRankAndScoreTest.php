<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRankAndScoreTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserRankAndScoreUnknownUser(): void
    {
        $this->user->RAPoints = 600; // make sure enough points to be ranked
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

    public function testGetUserRankAndScore(): void
    {
        $this->user->RAPoints = 600; // make sure enough points to be ranked
        $this->user->save();

        /** @var User $user */
        $user = User::factory()->create([
            'RASoftcorePoints' => 371,
            'RAPoints' => 25842,
        ]);

        $this->get($this->apiUrl('GetUserRankAndScore', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Score' => $user->RAPoints,
                'SoftcoreScore' => $user->RASoftcorePoints,
                'Rank' => 1,
                'TotalRanked' => 2, // $this->user and $user
            ]);
    }
}
