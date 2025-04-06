<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\BuildClientPatchDataAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
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
        string $imagePath = '/Images/000011.png',
        ?string $richPresencePatch = "Display:\nTest",
    ): Game {
        $game = Game::factory()->create([
            'Title' => $title,
            'ConsoleID' => $system->id,
            'ImageIcon' => $imagePath,
            'RichPresencePatch' => $richPresencePatch,
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
        $this->assertEquals($game->id, $patchData['ParentID']);
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

    public function testItHandlesZeroPlayerCountCorrectly(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Zero Player Game', publishedCount: 1);
        $this->upsertGameCoreSetAction->execute($game);

        $achievement = Achievement::firstWhere('GameID', $game->id);
        $achievement->unlocks_total = 0;
        $achievement->unlocks_hardcore_total = 0;
        $achievement->save();

        $game->players_total = 0; // !!
        $game->save();

        $user = User::factory()->create();

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['PatchData']['Achievements']);

        $this->assertAchievementData(
            $result['PatchData']['Achievements'][0],
            $achievement,
            100.00, // (0 + 1) / 1 * 100, capped at 100
            100.00  // (0 + 1) / 1 * 100, capped at 100
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
        $this->assertCount(1, $result['PatchData']['Sets']);

        // ... verify the base data includes ParentID ...
        $this->assertEquals($baseGame->id, $result['PatchData']['ID']);
        $this->assertEquals($baseGame->id, $result['PatchData']['ParentID']);

        // ... verify the core set is at the root level ...
        $this->assertCount(2, $result['PatchData']['Achievements']);

        // ... verify the bonus set is in the sets level ...
        $bonusSet = $result['PatchData']['Sets'][0];
        $this->assertEquals(AchievementSetType::Bonus->value, $bonusSet['Type']);
        $this->assertEquals($bonusGame->id, $bonusSet['GameID']); // Each set includes the game ID
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

    /**
     * If the user is globally opted out of subsets and they load a bonus subset
     * game's hash, then it's like the user is still living in the pre-multiset
     * world. The only set that resolves is the set for the subset game.
     */
    public function testGloballyOptedOutOfSubsetsAndLoadedSubsetHash(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', publishedCount: 2);
        $bonusGame2 = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus 2]', publishedCount: 3);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($bonusGame2);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame2, AchievementSetType::Bonus, 'Bonus 2');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_DISABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(
            gameHash: $bonusGameHash,
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);

        $this->assertEquals(2, $result['PatchData']['ID']);
        $this->assertEquals('Dragon Quest III [Subset - Bonus]', $result['PatchData']['Title']);
        $this->assertCount(2, $result['PatchData']['Achievements']);

        $this->assertArrayNotHasKey('Sets', $result['PatchData']);
    }

    /**
     * If the user has multiset enabled, they load a bonus subset game's hash, but are locally
     * opted out of that subset, then we treat it like they loaded a core game hash. They'll
     * receive the core set and any other bonus sets, but not the set they've opted out of.
     */
    public function testLocallyOptedOutOfSubsetsAndLoadedOptedOutSubsetHash(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 1,
            imagePath: '/Images/000001.png',
            richPresencePatch: 'Foo',
        );
        $bonusGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Bonus]',
            publishedCount: 2,
            imagePath: '/Images/000002.png',
            richPresencePatch: 'Bar',
        );
        $bonusGame2 = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus 2]', publishedCount: 3);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($bonusGame2);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus'); // !!
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame2, AchievementSetType::Bonus, 'Bonus 2');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // They're going to load a hash for $bonusGame, but they're also locally opted out of
        // $bonusGame's achievement set.
        $optOutSet = GameAchievementSet::firstWhere('title', 'Bonus'); // !!
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $user->id,
            'game_achievement_set_id' => GameAchievementSet::whereGameId($baseGame->id)
                ->whereType(AchievementSetType::Bonus)
                ->whereAchievementSetId($optOutSet->achievement_set_id)
                ->first()
                ->id,
            'opted_in' => false,
        ]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(
            gameHash: $bonusGameHash,
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);

        $this->assertEquals($baseGame->id, $result['PatchData']['ID']);
        $this->assertEquals($baseGame->title, $result['PatchData']['Title']);
        $this->assertEquals($baseGame->ImageIcon, $result['PatchData']['ImageIcon']);
        $this->assertCount(1, $result['PatchData']['Achievements']);

        $this->assertEquals($baseGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);

        $this->assertCount(1, $result['PatchData']['Sets']);

        $this->assertEquals('bonus', $result['PatchData']['Sets'][0]['Type']);
        $this->assertEquals('Bonus 2', $result['PatchData']['Sets'][0]['SetTitle']);
        $this->assertCount(3, $result['PatchData']['Sets'][0]['Achievements']);
    }

    public function testItPrioritizesSpecialtySetRichPresenceScript(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 1,
            imagePath: '/Images/000001.png',
            richPresencePatch: 'Foo',
        );
        $specialtyGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Special]',
            publishedCount: 2,
            imagePath: '/Images/000002.png',
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Special');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $specialtyGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($specialtyGame->id, $result['PatchData']['ID']); // ... use subset game's ID ...
        $this->assertEquals($specialtyGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']); // ... and specialty RP.
    }

    public function testItPrioritizesExclusiveSetRichPresenceScript(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 1,
            richPresencePatch: 'Foo',
        );
        $exclusiveGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Exclusive]',
            publishedCount: 2,
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($exclusiveGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive');

        $exclusiveGameHash = GameHash::factory()->create(['game_id' => $exclusiveGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $exclusiveGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($exclusiveGame->id, $result['PatchData']['ID']); // ... use subset game's ID ...
        $this->assertEquals($exclusiveGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']); // ... but exclusive RP.
    }

    public function testItFallsBackToCoreSetRichPresenceScript(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 1,
            imagePath: '/Images/000001.png',
            richPresencePatch: 'Foo',
        );
        $specialtyGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Special]',
            publishedCount: 2,
            imagePath: '/Images/000002.png',
        );

        $specialtyGame->RichPresencePatch = ""; // !!
        $specialtyGame->save();

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Special');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $specialtyGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($baseGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);
    }

    public function testItUsesCoreGameRichPresenceForBonusSet(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 1,
            richPresencePatch: 'Foo',
        );
        $bonusGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Bonus]',
            publishedCount: 2,
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $bonusGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($baseGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);
    }

    public function testItResolvesRootDataCorrectlyForSpecialtySet(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 3,
            richPresencePatch: 'Foo',
        );
        $specialtyGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Special]',
            publishedCount: 2,
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Special');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $specialtyGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);

        // ... title and image should be from the base game ...
        $this->assertEquals($baseGame->title, $result['PatchData']['Title']);
        $this->assertEquals($baseGame->ImageIcon, $result['PatchData']['ImageIcon']);

        // ... id should be from the subset but ParentID should point to the base game ...
        $this->assertEquals($specialtyGame->id, $result['PatchData']['ID']);
        $this->assertEquals($baseGame->id, $result['PatchData']['ParentID']);
        $this->assertCount(2, $result['PatchData']['Achievements']); // the subset game's achievements

        // ... RP should be from the specialty game ...
        $this->assertEquals($specialtyGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);
    }

    public function testItResolvesRootDataCorrectlyForExclusiveSet(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 3,
            richPresencePatch: 'Foo',
        );
        $exclusiveGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Exclusive]',
            publishedCount: 2,
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($exclusiveGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive');

        $exclusiveGameHash = GameHash::factory()->create(['game_id' => $exclusiveGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $exclusiveGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);

        // ... title and image should be from the base game ...
        $this->assertEquals($baseGame->title, $result['PatchData']['Title']);
        $this->assertEquals($baseGame->ImageIcon, $result['PatchData']['ImageIcon']);

        // ... id should be from the subset but ParentID should point to the base game ...
        $this->assertEquals($exclusiveGame->id, $result['PatchData']['ID']);
        $this->assertEquals($baseGame->id, $result['PatchData']['ParentID']);
        $this->assertCount(2, $result['PatchData']['Achievements']); // the subset game's achievements

        // ... RP should be from the exclusive game ...
        $this->assertEquals($exclusiveGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);

        // ... sets should not be present, as it is duplicative ...
        $this->assertArrayNotHasKey('Sets', $result['PatchData']);
    }

    public function testItHandlesGameWithNoAchievementSets(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 0,
            unpublishedCount: 0,
        );

        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $gameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEmpty($result['PatchData']['Achievements']);
        $this->assertEmpty($result['PatchData']['Sets'] ?? []);
        $this->assertEquals($game->id, $result['PatchData']['ID']);
        $this->assertEquals($game->RichPresencePatch, $result['PatchData']['RichPresencePatch']);
    }

    public function testItHandlesSubsetWithNoCoreGame(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 0,
            unpublishedCount: 0,
            richPresencePatch: 'Foo',
        );
        $subsetGame = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III [Subset - Gold Medals]',
            publishedCount: 2,
            richPresencePatch: 'Bar',
        );

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($subsetGame);
        $this->associateAchievementSetToGameAction->execute($baseGame, $subsetGame, AchievementSetType::Specialty, 'Gold Medals');

        $subsetGameHash = GameHash::factory()->create(['game_id' => $subsetGame->id]);
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $subsetGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);

        $this->assertEquals($subsetGame->id, $result['PatchData']['ID']);
        $this->assertEquals($subsetGame->RichPresencePatch, $result['PatchData']['RichPresencePatch']);

        $this->assertCount(2, $result['PatchData']['Achievements']);

        // no achievements for the base game and user loaded a subset hash. therefore, no sets.
        $this->assertArrayNotHasKey('Sets', $result['PatchData']);
    }

    public function testItBuildsPatchDataWithGameHashAndNullUser(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Hash Null User Game', publishedCount: 6);
        $this->upsertGameCoreSetAction->execute($baseGame);

        $gameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $gameHash, user: null);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertBaseGameData($result['PatchData'], $baseGame);
        $this->assertCount(6, $result['PatchData']['Achievements']);
    }

    public function testItDoesntCrashFromNullRichPresencePatch(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements(
            $this->system,
            'Dragon Quest III',
            publishedCount: 6,
            richPresencePatch: null, // !!
        );

        $this->upsertGameCoreSetAction->execute($game);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(6, $result['PatchData']['Achievements']);

        $this->assertNull($result['PatchData']['RichPresencePatch']);
    }

    public function testItResolvesSetTypesForBaseGameHashesCorrectly(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Multi-Set Game', publishedCount: 2);
        $bonusSet = $this->createGameWithAchievements($this->system, 'Multi-Set Game [Subset - Bonus]', publishedCount: 1);
        $bonusSet2 = $this->createGameWithAchievements($this->system, 'Multi-Set Game [Subset - Bonus 2]', publishedCount: 1);
        $specialtySet = $this->createGameWithAchievements($this->system, 'Multi-Set Game [Subset - Specialty]', publishedCount: 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusSet);
        $this->upsertGameCoreSetAction->execute($bonusSet2);
        $this->upsertGameCoreSetAction->execute($specialtySet);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusSet, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusSet2, AchievementSetType::Bonus, 'Bonus 2');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtySet, AchievementSetType::Specialty, 'Specialty');

        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]); // !!
        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $result = (new BuildClientPatchDataAction())->execute(gameHash: $baseGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertArrayHasKey('Sets', $result['PatchData']);
        $this->assertCount(2, $result['PatchData']['Sets']); // only bonus sets, core is at the root level

        // ... verify the ID and ParentID match for root game ...
        $this->assertEquals($baseGame->id, $result['PatchData']['ID']);
        $this->assertEquals($baseGame->id, $result['PatchData']['ParentID']);

        // ... verify core is at the root level ...
        $this->assertCount(2, $result['PatchData']['Achievements']);

        // ... verify each set has a GameID field ...
        foreach ($result['PatchData']['Sets'] as $set) {
            $this->assertArrayHasKey('GameID', $set);
        }

        // ... verify the bonus set GameIDs ...
        $setGameIds = array_column($result['PatchData']['Sets'], 'GameID');
        $this->assertContains($bonusSet->id, $setGameIds);
        $this->assertContains($bonusSet2->id, $setGameIds);

        $setTypes = array_column($result['PatchData']['Sets'], 'Type');
        $this->assertContains(AchievementSetType::Bonus->value, $setTypes);
        $this->assertNotContains(AchievementSetType::Core->value, $setTypes);
        $this->assertNotContains(AchievementSetType::Specialty->value, $setTypes);
    }
}
