<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\PlayerAchievement;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\StaticData;
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
        $user = User::factory()->create();
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

    public function testGetAchievementDistribution(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        PlayerAchievement::factory()->hardcore()->create(['AchievementID' => $publishedAchievements->get(0)->ID, 'User' => $this->user->User]);
        PlayerAchievement::factory()->hardcore()->create(['AchievementID' => $publishedAchievements->get(1)->ID, 'User' => $this->user->User]);

        $unpublishedAchievements = Achievement::factory()->count(5)->create(['GameID' => $game->ID]);
        PlayerAchievement::factory()->hardcore()->create(['AchievementID' => $unpublishedAchievements->get(0)->ID, 'User' => $this->user->User]);
        PlayerAchievement::factory()->create(['AchievementID' => $unpublishedAchievements->get(1)->ID, 'User' => $this->user->User]);
        PlayerAchievement::factory()->create(['AchievementID' => $unpublishedAchievements->get(2)->ID, 'User' => $this->user->User]);
        PlayerAchievement::factory()->create(['AchievementID' => $unpublishedAchievements->get(3)->ID, 'User' => $this->user->User]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => -1]))
            ->assertSuccessful()
            ->assertJson([]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Hardcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 1,
                '3' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Softcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 0,
                '3' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Hardcore, 'f' => AchievementType::Unofficial]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 1,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Softcore, 'f' => AchievementType::Unofficial]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 0,
                '3' => 1,
                '4' => 0,
                '5' => 0,
            ]);
    }

    public function testGetAchievementOfTheWeek(): void
    {
        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null], 'StartAt' => null]);

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create(['GameID' => $game->ID]);
        /** @var PlayerAchievement $unlock */
        $unlock = PlayerAchievement::factory()->create(['AchievementID' => $achievement->ID, 'User' => $this->user->User]);

        $staticData = StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement->ID,
            'Event_AOTW_StartAt' => Carbon::now()->subDay(),
        ]);

        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                ],
                'Console' => [
                    'ID' => $system->ID,
                ],
                'ForumTopic' => [
                    'ID' => 1,
                ],
                'Game' => [
                    'ID' => $game->ID,
                ],
                'StartAt' => $staticData->Event_AOTW_StartAt->jsonSerialize(),
                'TotalPlayers' => 1,
                'Unlocks' => [
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'HardcoreMode' => $unlock->HardcoreMode,
                    ],
                ],
                'UnlocksCount' => 1,
            ]);
    }

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

    public function testGetAchievementUnlocks(): void
    {
        $this->get($this->apiUrl('GetAchievementUnlocks'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null]]);

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create(['GameID' => $game->ID]);
        /** @var PlayerAchievement $unlock */
        $unlock = PlayerAchievement::factory()->create(['AchievementID' => $achievement->ID, 'User' => $this->user->User]);

        $this->get($this->apiUrl('GetAchievementUnlocks', ['a' => $achievement->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                ],
                'Console' => [
                    'ID' => $system->ID,
                ],
                'Game' => [
                    'ID' => $game->ID,
                ],
                'TotalPlayers' => 1,
                'Unlocks' => [
                    [
                        'User' => $this->user->User,
                        'RAPoints' => $this->user->RAPoints,
                        'HardcoreMode' => $unlock->HardcoreMode,
                    ],
                ],
                'UnlocksCount' => 1,
            ]);
    }

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
