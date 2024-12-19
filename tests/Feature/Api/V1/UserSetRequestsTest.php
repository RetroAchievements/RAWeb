<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSetRequestsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    /**
     * Test that the API returns an empty response for a non-existent user.
     */
    public function testGetUserSetRequestsUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserSetRequests', ['u' => 'nonExistant']))
            ->assertStatus(404)
            ->assertJson([]);
    }

    /**
     * Test that the API returns the correct set requests for an existing user.
     */
    public function testGetUserSetRequests(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
        ]);

        $user = User::factory()->create([
            'RAPoints' => 1501, // enough points to set request total to 1
        ]);

        // Create a user game list entry for the set request
        UserGameListEntry::factory()->create([
            'user_id' => $user->ID,
            'type' => 'achievement_set_request',
            'GameID' => $game->ID,
        ]);

        $this->get($this->apiUrl('GetUserSetRequests', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'RequestedSets' => [
                    [
                        'GameID' => $game->ID,
                        'Title' => $game->Title,
                        'ConsoleID' => $game->ConsoleID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game->ImageIcon,
                    ],
                ],
                'TotalRequests' => 1,
                'PointsForNext' => 999,
            ]);
    }
}
