<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\User;
use Tests\TestCase;

class V1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create(['User' => 'user', 'APIKey' => 'apiKey']);
        $this->user = $user;
    }

    private function apiUrl(string $method, array $params = []): string
    {
        $params = array_merge(['y' => $this->user->APIKey], $params);

        return sprintf('API/API_%s.php?%s', $method, http_build_query($params));
    }

    public function testUnauthorizedResponse(): void
    {
        $this->get('API/API_GetConsoleIDs.php')
            ->assertUnauthorized();
    }

    public function testGetAchievementCountEmptyResponse(): void
    {
        $this->postJson($this->apiUrl('GetAchievementCount', ['i' => 99]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'AchievementIDs' => [],
                'GameID' => 99,
            ]);
    }

    public function testGetAchievementCount(): void
    {
        /** @var Game $game */
        $game = Game::factory()
            ->has(Achievement::factory()->published()->count(3))
            ->create();

        $this->get($this->apiUrl('GetAchievementCount', ['i' => $game->ID]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'AchievementIDs' => $game->achievements->pluck('ID'),
                'GameID' => $game->ID,
            ]);
    }

    // public function testGetAchievementDistribution(): void
    // {
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     // /** @var Game $game */
    //     // $game = Game::factory()->create(['ConsoleID' => $system->ID]);
    //     // Achievement::factory()->count(3)->create(['GameID' => $game->ID, 'Author' => $user->User]);*/
    //
    //     $this->get($this->apiUrl('GetAchievementDistribution'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }

    // public function testGetAchievementOfTheWeek(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetAchievementOfTheWeek'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetAchievementsEarnedBetween(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetAchievementsEarnedBetween'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetAchievementsEarnedOnDay(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetAchievementsEarnedOnDay'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetAchievementUnlocks(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetAchievementUnlocks'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetActiveClaims(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetActiveClaims'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }

    public function testGetConsoleIds(): void
    {
        $systems = System::factory(3)->create();
        /** @var System $system */
        $system = $systems->first();

        $this->get($this->apiUrl('GetConsoleIDs'))
            ->assertSuccessful()
            ->assertJsonFragment([
                'ID' => $system->ID,
                'Name' => $system->Name,
            ]);
    }

    public function testGetFeed(): void
    {
        $this->get($this->apiUrl('GetFeed'))
            ->assertStatus(501);
    }

    // public function testGetGame(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGame'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetGameExtended(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGameExtended'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetGameInfoAndUserProgress(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGameInfoAndUserProgress'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetGameList(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGameList'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetGameRankAndScore(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGameRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetGameRating(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetGameRating'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetTicketData(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetTicketData'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetTopTenUsers(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetTopTenUsers'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserClaims(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserClaims'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserCompletedGames(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserCompletedGames'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserGameRankAndScore(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserGameRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserProgress(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserProgress'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserRankAndScore(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserRecentlyPlayedGames(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserRecentlyPlayedGames'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
    //
    // public function testGetUserSummary(): void
    // {
    //     // TODO
    //
    //     $systems = System::factory(3)->create();
    //     /** @var System $system */
    //     $system = $systems->first();
    //
    //     $this->get($this->apiUrl('GetUserSummary'))
    //         ->assertSuccessful()
    //         ->assertJsonFragment([
    //             'ID' => $system->ID,
    //             'Name' => $system->Name,
    //         ]);
    // }
}
