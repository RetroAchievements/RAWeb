<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
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
            ->has(Achievement::factory()->promoted()->count(3))
            ->create();

        $this->get($this->apiUrl('GetAchievementCount', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'AchievementIDs' => $game->achievements->pluck('id'),
                'GameID' => $game->id,
            ]);
    }

    public function testGetAchievementDistribution(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $publishedAchievements = Achievement::factory()->promoted()->count(5)->create(['game_id' => $game->id]);
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(1));

        $unpublishedAchievements = Achievement::factory()->count(5)->create(['game_id' => $game->id]);
        $this->addHardcoreUnlock($this->user, $unpublishedAchievements->get(0));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(1));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(2));
        $this->addSoftcoreUnlock($this->user, $unpublishedAchievements->get(3));

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => -1]))
            ->assertSuccessful()
            ->assertJson([]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->id, 'h' => UnlockMode::Hardcore]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 0,
                '2' => 1,
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->id, 'h' => UnlockMode::Softcore]))
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

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->id, 'h' => UnlockMode::Softcore]))
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
        $publishedAchievements->get(4)->is_promoted = false;
        $publishedAchievements->get(4)->save();

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->id, 'h' => UnlockMode::Hardcore, 'f' => Achievement::FLAG_UNPROMOTED]))
            ->assertSuccessful()
            ->assertExactJson([
                '1' => 1,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
            ]);

        $this->get($this->apiUrl('GetAchievementDistribution', ['i' => $game->id, 'h' => UnlockMode::Softcore, 'f' => Achievement::FLAG_UNPROMOTED]))
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

    public function testGetAchievementsEarnedBetweenEmptyResponse(): void
    {
        $this->get($this->apiUrl('GetAchievementsEarnedBetween'))
            ->assertStatus(422)
            ->assertJson([
                "message" => "The u field is required.",
            ]);
    }

    public function testGetAchievementsEarnedBetweenByUser(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 100]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $achievement = Achievement::first();

        $this->get(
            $this->apiUrl('GetAchievementsEarnedBetween', [
                'u' => $this->user->username, // !!
                'f' => Carbon::now()->subDay()->startOfDay()->unix(),
                't' => Carbon::now()->addDay()->endOfDay()->unix(),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->id,
                    'ConsoleName' => $system->name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->description,
                    'GameID' => $game->id,
                    'GameIcon' => $game->image_icon_asset_path,
                    'GameTitle' => $game->title,
                    'GameURL' => '/game/' . $game->id,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->points,
                    'TrueRatio' => $achievement->points_weighted,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->title,
                ],
            ]);
    }

    public function testGetAchievementsEarnedBetweenByUlid(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 100]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $achievement = Achievement::first();

        $this->get(
            $this->apiUrl('GetAchievementsEarnedBetween', [
                'u' => $this->user->ulid, // !!
                'f' => Carbon::now()->subDay()->startOfDay()->unix(),
                't' => Carbon::now()->addDay()->endOfDay()->unix(),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->id,
                    'ConsoleName' => $system->name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->description,
                    'GameID' => $game->id,
                    'GameIcon' => $game->image_icon_asset_path,
                    'GameTitle' => $game->title,
                    'GameURL' => '/game/' . $game->id,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->points,
                    'TrueRatio' => $achievement->points_weighted,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->title,
                ],
            ]);
    }

    public function testGetAchievementsEarnedOnDayByUser(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 100, 'user_id' => $this->user->id]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $this->get(
            $this->apiUrl('GetAchievementsEarnedOnDay', [
                'u' => $this->user->username, // !!
                'd' => $unlockTime->format('Y-m-d'),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->id,
                    'Author' => $this->user->username,
                    'AuthorULID' => $this->user->ulid,
                    'BadgeName' => $achievement->image_name,
                    'BadgeURL' => '/Badge/' . $achievement->image_name . '.png',
                    'ConsoleName' => $system->name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->description,
                    'GameID' => $game->id,
                    'GameIcon' => $game->image_icon_asset_path,
                    'GameTitle' => $game->title,
                    'GameURL' => '/game/' . $game->id,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->points,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->title,
                ],
            ]);
    }

    public function testGetAchievementsEarnedOnDayByUlid(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 100, 'user_id' => $this->user->id]);

        $unlockTime = Carbon::now()->subMinutes(5);
        $this->addSoftcoreUnlock($this->user, $achievement, $unlockTime);
        $this->addSoftcoreUnlock($this->user, $achievement, Carbon::now()->subDays(5));

        $this->get(
            $this->apiUrl('GetAchievementsEarnedOnDay', [
                'u' => $this->user->ulid, // !!
                'd' => $unlockTime->format('Y-m-d'),
            ])
        )
            ->assertSuccessful()
            ->assertJson([
                [
                    'AchievementID' => $achievement->id,
                    'Author' => $this->user->username,
                    'AuthorULID' => $this->user->ulid,
                    'BadgeName' => $achievement->image_name,
                    'BadgeURL' => '/Badge/' . $achievement->image_name . '.png',
                    'ConsoleName' => $system->name,
                    'CumulScore' => 100,
                    'Date' => $unlockTime->format('Y-m-d H:i:s'),
                    'Description' => $achievement->description,
                    'GameID' => $game->id,
                    'GameIcon' => $game->image_icon_asset_path,
                    'GameTitle' => $game->title,
                    'GameURL' => '/game/' . $game->id,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement->points,
                    'Type' => AchievementType::Progression,
                    'Title' => $achievement->title,
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
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->progression()->create([
            'game_id' => $game->id,
            'user_id' => $achievementAuthor->id,
        ]);

        $this->addSoftcoreUnlock($this->user, $achievement);

        $this->get($this->apiUrl('GetAchievementUnlocks', ['a' => $achievement->id]))
            ->assertSuccessful()
            ->assertJson([
                'Achievement' => [
                    'ID' => $achievement->id,
                    'Title' => $achievement->title,
                    'Description' => $achievement->description,
                    'Points' => $achievement->points,
                    'Type' => $achievement->type,
                    'Author' => $achievementAuthor->username,
                    'AuthorULID' => $achievementAuthor->ulid,
                ],
                'Console' => [
                    'ID' => $system->id,
                ],
                'Game' => [
                    'ID' => $game->id,
                ],
                'TotalPlayers' => 1,
                'Unlocks' => [
                    [
                        'User' => $this->user->username,
                        'ULID' => $this->user->ulid,
                        'RAPoints' => $this->user->points_hardcore,
                        'RASoftcorePoints' => $this->user->points,
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
                'ID' => $system->id,
                'Name' => $system->name,
                'Active' => isValidConsoleId($system->id),
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
}
