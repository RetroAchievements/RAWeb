<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\BuildClientPatchDataV2Action;
use App\Enums\GameHashCompatibility;
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

class BuildClientPatchDataV2ActionTest extends TestCase
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
     * Helper method to verify the root data structure in V2 patch data.
     */
    private function assertBaseGameData(array $patchData, Game $game): void
    {
        $this->assertEquals($game->id, $patchData['GameId']);
        $this->assertEquals($game->title, $patchData['Title']);
        $this->assertEquals($game->system->id, $patchData['ConsoleId']);
        $this->assertEquals(media_asset($game->ImageIcon), $patchData['ImageIconUrl']);
        $this->assertEquals($game->RichPresencePatch, $patchData['RichPresencePatch']);
        $this->assertEquals($game->id, $patchData['RichPresenceGameId']);
    }

    /**
     * Helper method to verify achievement data structure and contents.
     */
    private function assertAchievementData(
        array $achievementData,
        Achievement $achievement,
        float $expectedRarity,
        float $expectedRarityHardcore
    ): void {
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
        $this->expectExceptionMessage('Either gameHash or game must be provided to build achievementsets data.');

        (new BuildClientPatchDataV2Action())->execute();
    }

    public function testItReturnsBaseGameDataWithNoAchievements(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'ConsoleID' => $this->system->id,
            'ImageIcon' => '/Images/000011.png',
            'RichPresencePatch' => "Display:\nTest",
        ]);
        $this->upsertGameCoreSetAction->execute($game);

        // Act
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertBaseGameData($result, $game);
        $this->assertCount(1, $result['Sets']); // !! always return an empty achievement set
        $this->assertEmpty($result['Sets'][0]['Achievements']);
        $this->assertEmpty($result['Sets'][0]['Leaderboards']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['Sets']);
        $this->assertCount(1, $result['Sets'][0]['Achievements']);

        // For a new player, rarity calculation should use players_total + 1.
        // Rarity = (unlocks + 1) / (players + 1) * 100
        $this->assertAchievementData(
            $result['Sets'][0]['Achievements'][0],
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
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['Sets']);
        $this->assertCount(1, $result['Sets'][0]['Achievements']);

        // For an existing player, use the actual players_total value.
        // Rarity = (unlocks + 1) / players * 100
        $this->assertAchievementData(
            $result['Sets'][0]['Achievements'][0],
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
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['Sets']);
        $this->assertCount(1, $result['Sets'][0]['Achievements']);

        $this->assertAchievementData(
            $result['Sets'][0]['Achievements'][0],
            $achievement,
            100.00, // (0 + 1) / 1 * 100, capped at 100
            100.00  // (0 + 1) / 1 * 100, capped at 100
        );
    }

    public function testItIncludesLeaderboardData(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Dragon Quest III', publishedCount: 1);
        $this->upsertGameCoreSetAction->execute($game);

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
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(2, $result['Sets'][0]['Leaderboards']);

        $leaderboardData = $result['Sets'][0]['Leaderboards'];

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
        $result = (new BuildClientPatchDataV2Action())->execute(
            game: $game,
            flag: AchievementFlag::OfficialCore // !!
        );

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(2, $result['Sets'][0]['Achievements']);

        foreach ($result['Sets'][0]['Achievements'] as $achievementData) {
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $gameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertArrayHasKey('Sets', $result);
        $this->assertCount(2, $result['Sets']); // !! both core and bonus sets included

        $coreSet = $result['Sets'][0];
        $this->assertEquals(AchievementSetType::Core->value, $coreSet['Type']);
        $this->assertCount(2, $coreSet['Achievements']);

        $bonusSet = $result['Sets'][1];
        $this->assertEquals(AchievementSetType::Bonus->value, $bonusSet['Type']);
        $this->assertEquals($bonusGame->id, $bonusSet['GameId']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(
            game: $baseGame,
            gameHash: null, // !!
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($baseGame->id, $result['GameId']);
        $this->assertCount(1, $result['Sets']); // !! only core set for legacy clients
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
        $result = (new BuildClientPatchDataV2Action())->execute(
            gameHash: $bonusGameHash,
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);

        $this->assertEquals($bonusGame->id, $result['GameId']);
        $this->assertEquals('Dragon Quest III [Subset - Bonus]', $result['Title']);
        $this->assertCount(1, $result['Sets']); // !! just one set
        $this->assertCount(2, $result['Sets'][0]['Achievements']);
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

        // They're going to load a hash for $bonusGame, but they're also
        // locally opted out of $bonusGame's achievement set.
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
        $result = (new BuildClientPatchDataV2Action())->execute(
            gameHash: $bonusGameHash,
            user: $user
        );

        // Assert
        $this->assertTrue($result['Success']);

        $this->assertEquals($baseGame->id, $result['GameId']);
        $this->assertEquals($baseGame->title, $result['Title']);
        $this->assertEquals(media_asset($baseGame->ImageIcon), $result['ImageIconUrl']);

        $this->assertCount(2, $result['Sets']); // !! core set and bonus2 set

        $this->assertEquals(AchievementSetType::Core->value, $result['Sets'][0]['Type']);
        $this->assertCount(1, $result['Sets'][0]['Achievements']);

        $this->assertEquals(AchievementSetType::Bonus->value, $result['Sets'][1]['Type']);
        $this->assertEquals('Bonus 2', $result['Sets'][1]['Title']);
        $this->assertCount(3, $result['Sets'][1]['Achievements']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $baseGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertArrayHasKey('Sets', $result);
        $this->assertCount(3, $result['Sets']); // Core, 2 bonus sets, and specialty

        // ... verify the GameId and Title at the root level ...
        $this->assertEquals($baseGame->id, $result['GameId']);
        $this->assertEquals($baseGame->title, $result['Title']);

        // ... verify each set has its correct data ...
        $setTypes = [];
        $setTitles = [];
        $setGameIds = [];

        foreach ($result['Sets'] as $set) {
            $setTypes[] = $set['Type'];
            $setTitles[] = $set['Title'];
            $setGameIds[] = $set['GameId'];
        }

        $this->assertContains(AchievementSetType::Core->value, $setTypes);
        $this->assertContains(AchievementSetType::Bonus->value, $setTypes);
        $this->assertNotContains(AchievementSetType::Specialty->value, $setTypes); // !! loaded a core hash, specialty shouldn't be included

        $this->assertContains('Bonus', $setTitles);
        $this->assertContains('Bonus 2', $setTitles);
        $this->assertNotContains('Specialty', $setTitles);

        $this->assertContains($bonusSet->id, $setGameIds);
        $this->assertContains($bonusSet2->id, $setGameIds);
        $this->assertNotContains($specialtySet->id, $setGameIds);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $specialtyGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($specialtyGame->id, $result['RichPresenceGameId']);
        $this->assertEquals($specialtyGame->RichPresencePatch, $result['RichPresencePatch']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $exclusiveGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($exclusiveGame->id, $result['RichPresenceGameId']);
        $this->assertEquals($exclusiveGame->RichPresencePatch, $result['RichPresencePatch']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $specialtyGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($baseGame->id, $result['RichPresenceGameId']);
        $this->assertEquals($baseGame->RichPresencePatch, $result['RichPresencePatch']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $bonusGameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($baseGame->id, $result['RichPresenceGameId']);
        $this->assertEquals($baseGame->RichPresencePatch, $result['RichPresencePatch']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(game: $game);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['Sets']);
        $this->assertCount(6, $result['Sets'][0]['Achievements']);

        $this->assertNull($result['RichPresencePatch']);
    }

    public function testItHandlesIncompatibleGameHash(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Incompatible Game', publishedCount: 2);
        $this->upsertGameCoreSetAction->execute($game);

        $gameHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Incompatible,
            'compatibility_tester_id' => null,
        ]);

        // Act
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $gameHash);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals('Unsupported Game Version', $result['Title']);
        $this->assertCount(1, $result['Sets']);

        $this->assertCount(1, $result['Sets'][0]['Achievements']);
        $this->assertEquals('Unsupported Game Version', $result['Sets'][0]['Achievements'][0]['Title']);
        $this->assertMatchesRegularExpression('/this version of the game is known to not work/i', $result['Sets'][0]['Achievements'][0]['Description']);
    }

    public function testItHandlesIncompatibleGameHashButUserIsTester(): void
    {
        // Arrange
        $game = $this->createGameWithAchievements($this->system, 'Test Game', publishedCount: 2);
        $this->upsertGameCoreSetAction->execute($game);

        $user = User::factory()->create();
        $gameHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Untested,
            'compatibility_tester_id' => $user->id, // !!
        ]);

        // Act
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $gameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertEquals($game->title, $result['Title']);
        $this->assertCount(1, $result['Sets']);

        // ... it should include the actual achievements, not a warning ...
        $this->assertCount(2, $result['Sets'][0]['Achievements']);
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
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $gameHash, user: $user);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertCount(1, $result['Sets']);
        $this->assertEmpty($result['Sets'][0]['Achievements']);
        $this->assertEquals($game->id, $result['GameId']);
        $this->assertEquals($game->RichPresencePatch, $result['RichPresencePatch']);
    }

    public function testItHandlesBuildPatchDataWithGameHashAndNullUser(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Hash Null User Game', publishedCount: 6);
        $this->upsertGameCoreSetAction->execute($baseGame);

        $gameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        // Act
        $result = (new BuildClientPatchDataV2Action())->execute(gameHash: $gameHash, user: null);

        // Assert
        $this->assertTrue($result['Success']);
        $this->assertBaseGameData($result, $baseGame);
        $this->assertCount(1, $result['Sets']);
        $this->assertCount(6, $result['Sets'][0]['Achievements']);
    }
}
