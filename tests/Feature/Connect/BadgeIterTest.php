<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeIterTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testGetBadgeIterEmptyDatabase(): void
    {
        $this->get($this->apiUrl('badgeiter', credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'FirstBadge' => 1,
                'NextBadge' => 1,
            ]);
    }

    public function testGetBadgeIterFromAchievements(): void
    {
        $game = $this->seedGame();
        Achievement::factory()->create(['BadgeName' => '00345']);
        Achievement::factory()->create(['BadgeName' => '10942']);
        Achievement::factory()->create(['BadgeName' => '00611']);
        Achievement::factory()->create(['BadgeName' => '02577']);

        $this->get($this->apiUrl('badgeiter', credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'FirstBadge' => 345,
                'NextBadge' => 10943,
            ]);
    }
}
