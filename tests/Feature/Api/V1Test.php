<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\StaticData;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\V1\BootstrapsApiV1;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class V1Test extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

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

        $publishedAchievements = Achievement::factory()->published()->count(5)->create(['GameID' => $game->ID]);
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(1));

        $unpublishedAchievements = Achievement::factory()->count(5)->create(['GameID' => $game->ID]);
        $this->addHardcoreUnlock($this->user, $unpublishedAchievements->get(0));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(1));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(2));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(3));

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => -1]))
            ->assertSuccessful()
            ->assertJson([]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Hardcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 1,
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Softcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 0, // hardcore no longer counts toward softcore
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ]);

        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(2));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(3));

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Softcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 1, // Now that softcore cheevos are added, this should see them
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ]);

        // Unlocks can't be granted while an achievement is in unofficial status.
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(4));
        $publishedAchievements->get(4)->Flags = AchievementFlag::Unofficial;
        $publishedAchievements->get(4)->save();

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Hardcore, 'f' => AchievementFlag::Unofficial]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 1,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->ID, 'h' => UnlockMode::Softcore, 'f' => AchievementFlag::Unofficial]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 1, // this now counts softcore unlocks instead of total (softcore + hardcore)
                '5' => 0,
                '6' => 0,
            ]);
    }

    public function testGetAchievementOfTheWeekEmptyResponse(): void
    {
        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null], 'StartAt' => null]);
    }

    public function testGetAchievementOfTheWeek(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        $this->addSoftcoreUnlock($this->user, $achievement);

        $staticData = StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement->ID,
            'Event_AOTW_StartAt' => Carbon::now()->subDay(),
        ]);

        $this->get($this->apiUrl('GetAchievementOfTheWeek'))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                    'BadgeName' => $achievement->BadgeName,
                    'BadgeURL' => "/Badge/{$achievement->BadgeName}.png",
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
                        'HardcoreMode' => 0,
                    ],
                ],
                'UnlocksCount' => 1,
                'UnlocksHardcoreCount' => 0,
            ]);
    }

    public function testGetAchievementsEarnedBetweenEmptyResponse(): void
    {
        $this->get($this->apiUrl('GetAchievementsEarnedBetween'))
            ->assertSuccessful()
            ->assertExactJson([]);
    }

    public function testGetAchievementsEarnedBetween(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'Points' => 100]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $achievement = Achievement::first();

        $this->get(
            $this->apiUrl('GetAchievementsEarnedBetween', [
                'u' => $this->user->User,
                'f' => Carbon::now()->subDay()->startOfDay()->unix(),
                't' => Carbon::now()->addDay()->endOfDay()->unix(),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->ID,
                    'ConsoleName' => $system->Name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->Description,
                    'GameID' => $game->ID,
                    'GameIcon' => $game->ImageIcon,
                    'GameTitle' => $game->Title,
                    'GameURL' => '/game/' . $game->ID,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->Points,
                    'TrueRatio' => $achievement->points_weighted,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->Title,
                ],
            ]);
    }

    public function testGetAchievementsEarnedOnDay(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'Points' => 100, 'Author' => $this->user->User, 'user_id' => $this->user->id]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $this->get(
            $this->apiUrl('GetAchievementsEarnedOnDay', [
                'u' => $this->user->User,
                'd' => $unlockTime->format('Y-m-d'),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->ID,
                    'Author' => $this->user->User,
                    'BadgeName' => $achievement->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement->BadgeName . '.png',
                    'ConsoleName' => $system->Name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->Description,
                    'GameID' => $game->ID,
                    'GameIcon' => $game->ImageIcon,
                    'GameTitle' => $game->Title,
                    'GameURL' => '/game/' . $game->ID,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->Points,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->Title,
                ],
            ]);
    }

    public function testGetAchievementUnlocks(): void
    {
        $this->get($this->apiUrl('GetAchievementUnlocks'))
            ->assertSuccessful()
            ->assertJson(['Achievement' => ['ID' => null]]);

        /** @var User $achievementAuthor */
        $achievementAuthor = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->progression()->create([
            'GameID' => $game->id,
            'user_id' => $achievementAuthor->id,
        ]);

        $this->addSoftcoreUnlock($this->user, $achievement);

        $this->get($this->apiUrl('GetAchievementUnlocks', ['a' => $achievement->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->ID,
                    'Title' => $achievement->Title,
                    'Description' => $achievement->Description,
                    'Points' => $achievement->Points,
                    'Type' => $achievement->type,
                    'Author' => $achievementAuthor->User,
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
                        'HardcoreMode' => 0,
                    ],
                ],
                'UnlocksCount' => 1,
                'UnlocksHardcoreCount' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementUnlocks', ['a' => 999999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Achievement' => [],
                'TotalPlayers' => 0,
                'Unlocks' => [],
                'UnlocksCount' => 0,
                'UnlocksHardcoreCount' => 0,
            ]);
    }

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
                'Active' => isValidConsoleId($system->ID),
                'IsGameSystem' => true,
            ]);
    }

    public function testGetFeed(): void
    {
        $this->get($this->apiUrl('GetFeed'))
            ->assertStatus(410);
    }

    public function testGetGameRating(): void
    {
        $this->get($this->apiUrl('GetGameRating'))
            ->assertStatus(410);
    }

    // public function testGetGame(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetGame'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetGameExtended(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetGameExtended'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetGameList(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetGameList'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetGameRankAndScore(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetGameRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetTicketData(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetTicketData'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetTopTenUsers(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetTopTenUsers'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserCompletedGames(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserCompletedGames'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserGameRankAndScore(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserGameRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserProgress(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserProgress'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserRankAndScore(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserRankAndScore'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserRecentlyPlayedGames(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserRecentlyPlayedGames'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
    //
    // public function testGetUserSummary(): void
    // {
    //     // TODO
    //
    //     $this->get($this->apiUrl('GetUserSummary'))
    //         ->assertSuccessful()
    //         ->assertExactJson([]);
    // }
}
