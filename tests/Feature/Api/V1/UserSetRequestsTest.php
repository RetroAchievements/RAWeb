<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserGameListType;
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
     * Test that the API returns all set requests for an existing user.
     */
    public function testGetAllUserSetRequests(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
            'achievements_published' => 0,

        ]);
        $game2 = Game::factory()->create([
            'Title' => '~Hack~ Test Case',
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001235.png',
            'achievements_published' => 12,
        ]);

        $user = User::factory()->create([
            'RAPoints' => 1501, // enough points to set request total to 1
        ]);

        // Create the first user game list entry for the set request
        UserGameListEntry::factory()->create([
            'user_id' => $user->ID,
            'type' => UserGameListType::AchievementSetRequest,
            'GameID' => $game->ID,
        ]);
        // Create the second user game list entry for the set request
        UserGameListEntry::factory()->create([
            'user_id' => $user->ID,
            'type' => UserGameListType::AchievementSetRequest,
            'GameID' => $game2->ID,
        ]);

        $this->get($this->apiUrl('GetUserSetRequests', ['u' => $user->User, 't' => 1]))
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
                    [
                        'GameID' => $game2->ID,
                        'Title' => $game2->Title,
                        'ConsoleID' => $game2->ConsoleID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game2->ImageIcon,
                    ],
                ],
                'TotalRequests' => 1,
                'PointsForNext' => 999,
            ]);
    }

    /**
     * Test that the API returns only set requests with no published achievements for an existing user.
     */
    public function testGetUnpublishedUserSetRequests(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
            'achievements_published' => 4,
        ]);
        $game2 = Game::factory()->create([
            'Title' => '~Hack~ Test Case',
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001235.png',
            'achievements_published' => 0,
        ]);

        $user = User::factory()->create([
            'RAPoints' => 1501, // enough points to set request total to 1
        ]);

        // Create the first user game list entry for the set request
        UserGameListEntry::factory()->create([
            'user_id' => $user->ID,
            'type' => UserGameListType::AchievementSetRequest,
            'GameID' => $game->ID,
        ]);
        // Create the second user game list entry for the set request
        UserGameListEntry::factory()->create([
            'user_id' => $user->ID,
            'type' => UserGameListType::AchievementSetRequest,
            'GameID' => $game2->ID,
        ]);

        // Note that only the second game is present in the assert. The code will find the first game as well but only return the game with no published achievements.
        $this->get($this->apiUrl('GetUserSetRequests', ['u' => $user->User, 't' => 0]))
            ->assertSuccessful()
            ->assertJson([
                'RequestedSets' => [
                    [
                        'GameID' => $game2->ID,
                        'Title' => $game2->Title,
                        'ConsoleID' => $game2->ConsoleID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game2->ImageIcon,
                    ],
                ],
                'TotalRequests' => 1,
                'PointsForNext' => 999,
            ]);
    }
}
