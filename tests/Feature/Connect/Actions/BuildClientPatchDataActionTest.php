<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\BuildClientPatchDataAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BuildClientPatchDataActionTest extends TestCase
{
    use RefreshDatabase;

    public const OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED = 8439;
    public const OPT_IN_TO_ALL_SUBSETS_PREF_DISABLED = 270583;

    private System $system;
    private UpsertGameCoreAchievementSetFromLegacyFlagsAction $upsertGameCoreSetAction;
    private AssociateAchievementSetToGameAction $associateAchievementSetToGameAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['ID' => 1, 'Name' => 'NES/Famicom']);

        $this->upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $this->associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();
    }

    /**
     * Helper method to quickly create a game with achievements.
     */
    private function createGameWithAchievements(
        System $system,
        string $title,
        int $publishedCount,
        int $unpublishedCount = 0,
        string $imagePath = '/Images/000011.png'
    ): Game {
        $game = Game::factory()->create([
            'Title' => $title,
            'ConsoleID' => $system->id,
            'ImageIcon' => $imagePath,
            'RichPresencePatch' => "Display:\nTest",
        ]);

        Achievement::factory()->published()->count($publishedCount)->create(['GameID' => $game->id]);
        Achievement::factory()->count($unpublishedCount)->create(['GameID' => $game->id]);

        return $game;
    }

    /**
     * Helper method to verify the base game data structure in patch data.
     */
    private function assertBaseGameData(array $patchData, Game $game): void
    {
        $this->assertEquals($game->id, $patchData['ID']);
        $this->assertEquals($game->title, $patchData['Title']);
        $this->assertEquals($game->ConsoleID, $patchData['ConsoleID']);
        $this->assertEquals($game->ImageIcon, $patchData['ImageIcon']);
        $this->assertEquals($game->RichPresencePatch, $patchData['RichPresencePatch']);
        $this->assertEquals(media_asset($game->ImageIcon), $patchData['ImageIconURL']);
    }

    /**
     * Helper method to verify achievement data structure and contents.
     */
    private function assertAchievementData(array $achievementData, Achievement $achievement, float $expectedRarity, float $expectedRarityHardcore): void
    {
        $this->assertEquals($achievement->id, $achievementData['ID']);
        $this->assertEquals($achievement->title, $achievementData['Title']);
        $this->assertEquals($achievement->description, $achievementData['Description']);
        $this->assertEquals($achievement->MemAddr, $achievementData['MemAddr']);
        $this->assertEquals($achievement->points, $achievementData['Points']);
        $this->assertEquals($achievement->developer->display_name ?? '', $achievementData['Author']);
        $this->assertEquals($achievement->DateModified->unix(), $achievementData['Modified']);
        $this->assertEquals($achievement->DateCreated->unix(), $achievementData['Created']);
        $this->assertEquals($achievement->BadgeName, $achievementData['BadgeName']);
        $this->assertEquals($achievement->Flags, $achievementData['Flags']);
        $this->assertEquals($achievement->type, $achievementData['Type']);
        $this->assertEquals($expectedRarity, $achievementData['Rarity']);
        $this->assertEquals($expectedRarityHardcore, $achievementData['RarityHardcore']);
        $this->assertEquals($achievement->badge_unlocked_url, $achievementData['BadgeURL']);
        $this->assertEquals($achievement->badge_locked_url, $achievementData['BadgeLockedURL']);
    }

    public function testItThrowsExceptionWhenNoGameOrHashProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either gameHash or game must be provided to build patch data.');

        (new BuildClientPatchDataAction())->execute();
    }

    public function testItReturnsBaseGameDataWithNoAchievements(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'ConsoleID' => $this->system->id,
            'ImageIcon' => '/Images/000011.png',
            'RichPresencePatch' => "Display:\nTest",
        ]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertBaseGameData($result['PatchData'], $game);
        $this->assertEmpty($result['PatchData']['Achievements']);
        $this->assertEmpty($result['PatchData']['Leaderboards']);
    }

    public function testItCalculatesRarityForNewPlayer(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 1);
        $this->upsertGameCoreSetAction->execute($game);

        $achievement = Achievement::where('GameID', $game->id)->first();
        $achievement->unlocks_total = 49;
        $achievement->unlocks_hardcore_total = 24;
        $achievement->save();

        $game->players_total = 100;
        $game->save();

        $user = User::factory()->create();

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['PatchData']['Achievements']);

        // For a new player, rarity calculation should use players_total + 1.
        // Rarity = (unlocks + 1) / (players + 1) * 100
        $this->assertAchievementData(
            $result['PatchData']['Achievements'][0],
            $achievement,
            49.50, // (49 + 1) / 101 * 100
            24.75  // (24 + 1) / 101 * 100
        );
    }

    public function testItCalculatesRarityForExistingPlayer(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 1);
        $this->upsertGameCoreSetAction->execute($game);

        $achievement = Achievement::where('GameID', $game->id)->first();
        $achievement->unlocks_total = 49;
        $achievement->unlocks_hardcore_total = 24;
        $achievement->save();

        $game->players_total = 100;
        $game->save();

        $user = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['PatchData']['Achievements']);

        // For an existing player, use the actual players_total value.
        // Rarity = (unlocks + 1) / players * 100
        $this->assertAchievementData(
            $result['PatchData']['Achievements'][0],
            $achievement,
            50.00, // (49 + 1) / 100 * 100
            25.00  // (24 + 1) / 100 * 100
        );
    }

    public function testItIncludesLeaderboardData(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 1);

        $leaderboard1 = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'DisplayOrder' => 0,
            'Format' => 'SCORE',
        ]);
        $leaderboard2 = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'DisplayOrder' => -1, // !! hidden
            'Format' => 'VALUE',
        ]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(2, $result['PatchData']['Leaderboards']);

        $leaderboardData = $result['PatchData']['Leaderboards'];

        // ... hidden leaderboards should always come first ...
        $this->assertEquals($leaderboard2->id, $leaderboardData[0]['ID']);
        $this->assertEquals($leaderboard2->title, $leaderboardData[0]['Title']);
        $this->assertTrue($leaderboardData[0]['Hidden']);

        $this->assertEquals($leaderboard1->id, $leaderboardData[1]['ID']);
        $this->assertEquals($leaderboard1->title, $leaderboardData[1]['Title']);
        $this->assertFalse($leaderboardData[1]['Hidden']);
    }

    public function testItFiltersAchievementsByFlag(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 2,
            unpublishedCount: 1
        );
        $this->upsertGameCoreSetAction->execute($game);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(
            game: $game,
            flag: AchievementFlag::OfficialCore // !!
        );

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(2, $result['PatchData']['Achievements']);

        foreach ($result['PatchData']['Achievements'] as $achievementData) {
            $this->assertEquals(AchievementFlag::OfficialCore->value, $achievementData['Flags']);
        }
    }

    public function testItIncludesMultisetDataWhenUsingGameHash(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 2);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Gold Medals]', publishedCount: 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $gameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $gameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertArrayHasKey('Sets', $result['PatchData']);
        $this->assertCount(2, $result['PatchData']['Sets']);

        // ... verify the core set ...
        $coreSet = $result['PatchData']['Sets'][0];
        $this->assertEquals(AchievementSetType::Core->value, $coreSet['Type']);
        $this->assertCount(2, $coreSet['Achievements']);

        // ... verify the bonus set ...
        $bonusSet = $result['PatchData']['Sets'][1];
        $this->assertEquals(AchievementSetType::Bonus->value, $bonusSet['Type']);
        $this->assertCount(1, $bonusSet['Achievements']);
    }

    public function testItOmitsMultisetDataWhenUsingGameDirectlyLikeLegacyClients(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 2);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Gold Medals]', publishedCount: 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(
            game: $baseGame,
            gameHash: null, // !!
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertArrayNotHasKey('Sets', $result['PatchData']);
    }
}
