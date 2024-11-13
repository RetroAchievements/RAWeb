<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\ResolveAchievementSetsAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ResolveAchievementSetsActionTest extends TestCase
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

        // This common system is used in all the tests.
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
        int $unpublishedCount
    ): Game {
        $game = Game::factory()->create(['Title' => $title, 'ConsoleID' => $system->id]);
        Achievement::factory()->published()->count($publishedCount)->create(['GameID' => $game->id]);
        Achievement::factory()->count($unpublishedCount)->create(['GameID' => $game->id]);

        return $game;
    }

    /**
     * Helper method to assert the properties of an achievement set.
     */
    private function assertAchievementSet(
        GameAchievementSet $set,
        AchievementSetType $type,
        int $gameId,
        int $publishedAchievementCount,
        int $unpublishedAchievementCount
    ): void {
        $this->assertEquals($type, $set->type);
        $this->assertEquals($gameId, $set->game_id);

        $this->assertNotNull($set->achievementSet);

        $achievements = $set->achievementSet->achievements;
        $this->assertCount($publishedAchievementCount + $unpublishedAchievementCount, $achievements);

        $publishedCount = $achievements->where('Flags', AchievementFlag::OfficialCore->value)->count();
        $unpublishedCount = $achievements->where('Flags', AchievementFlag::Unofficial->value)->count();
        $this->assertEquals($publishedAchievementCount, $publishedCount);
        $this->assertEquals($unpublishedAchievementCount, $unpublishedCount);
    }

    /**
     * Helper method to check if a collection contains a set of a specific type.
     *
     * @param Collection<int, GameAchievementSet> $sets
     */
    private function assertContainsAchievementSetType(
        Collection $sets,
        AchievementSetType $type
    ): void {
        $contains = $sets->contains(fn ($set) => $set->type === $type);
        $this->assertTrue($contains);
    }

    /**
     * Helper method to check if a collection does not contain a set of a specific type.
     *
     * @param Collection<int, GameAchievementSet> $sets
     */
    private function assertNotContainsAchievementSetType(
        Collection $sets,
        AchievementSetType $type
    ): void {
        $contains = $sets->contains(fn ($set) => $set->type === $type);
        $this->assertFalse($contains);
    }

    public function testItReturnsCoreSetIfOnlyCoreSetIsPresent(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $this->upsertGameCoreSetAction->execute($baseGame);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($baseGameHash, $user);

        // Assert
        $this->assertCount(1, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 6, 1);
    }

    public function testItReturnsBonusSetsForCoreHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($baseGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 6, 1);

        $bonusSet = $resolved->last();
        $this->assertAchievementSet($bonusSet, AchievementSetType::Bonus, $baseGame->id, 8, 1);
    }

    public function testItDoesNotReturnSpecialtySetsForCoreHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);
        $specialtyGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Specialty]', 1, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($baseGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Core);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Bonus);
        $this->assertNotContainsAchievementSetType($resolved, AchievementSetType::Specialty);
    }

    public function testItReturnsSpecialtySetAndCoreBonusSetsForSpecialtyHash(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);
        $specialtyGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Specialty]', 1, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($specialtyGameHash, $user);

        // Assert
        $this->assertCount(3, $resolved);

        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Core);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Bonus);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Specialty);
    }

    public function testItAllowsSpecialtySetPlayersToOptOutOfTheCoreSet(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 5, 0);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 3, 0);
        $specialtyGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Specialty]', 1, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $user->id,
            'game_achievement_set_id' => GameAchievementSet::whereGameId($baseGame->id)
                ->whereType(AchievementSetType::Core)
                ->first()
                ->id,
            'opted_in' => false,
        ]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($specialtyGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Bonus);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Specialty);
        $this->assertNotContainsAchievementSetType($resolved, AchievementSetType::Core);
    }

    public function testItAllowsSpecialtySetPlayersToOptOutOfBonusSets(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 5, 0);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 3, 0);
        $specialtyGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Specialty]', 1, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $user->id,
            'game_achievement_set_id' => GameAchievementSet::whereGameId($baseGame->id)
                ->whereType(AchievementSetType::Bonus)
                ->first()
                ->id,
            'opted_in' => false,
        ]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($specialtyGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Core);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Specialty);
        $this->assertNotContainsAchievementSetType($resolved, AchievementSetType::Bonus);
    }

    public function testItReturnsCoreSetsForBonusHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($bonusGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 6, 1);

        $bonusSet = $resolved->last();
        $this->assertAchievementSet($bonusSet, AchievementSetType::Bonus, $baseGame->id, 8, 1);
    }

    public function testItExcludesSubsetsIfUserIsGloballyOptedOut(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_DISABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($baseGameHash, $user);

        // Assert
        $this->assertCount(1, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 6, 1);
    }

    public function testItIncludesSubsetIfUserIsGloballyOptedOutButLocallyOptedIn(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $baseGameHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_DISABLED]);

        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $user->id,
            'game_achievement_set_id' => GameAchievementSet::whereGameId($baseGame->id)
                ->whereType(AchievementSetType::Bonus)
                ->first()
                ->id,
            'opted_in' => true,
        ]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($baseGameHash, $user);

        // Assert
        $this->assertCount(2, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 6, 1);

        $bonusSet = $resolved->last();
        $this->assertAchievementSet($bonusSet, AchievementSetType::Bonus, $baseGame->id, 8, 1);
    }

    public function testItReturnsExclusiveSetAndNothingElseForExclusiveHash(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 5, 0);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 3, 0);
        $exclusiveGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Exclusive]', 6, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($exclusiveGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive');

        $exclusiveGameHash = GameHash::factory()->create(['game_id' => $exclusiveGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($exclusiveGameHash, $user);

        // Assert
        $this->assertCount(1, $resolved);

        $exclusiveSet = $resolved->first();
        $this->assertAchievementSet($exclusiveSet, AchievementSetType::Exclusive, $baseGame->id, 6, 0);
    }

    public function testItExcludesAchievementSetIfHashIsIncompatible(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 5, 0);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 3, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        // ... create multiple game hashes for base game ...
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $baseGame->id]);

        // ... mark one hash as incompatible with the bonus set ...
        $bonusSet = GameAchievementSet::where('game_id', $baseGame->id)
            ->where('type', AchievementSetType::Bonus)
            ->firstOrFail();
        $incompatibleHash = $gameHashes->first();
        $bonusSet->achievementSet->incompatibleGameHashes()->attach($incompatibleHash->id);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($incompatibleHash, $user); // !! $incompatibleHash

        // Assert
        $this->assertCount(1, $resolved);

        $coreSet = $resolved->first();
        $this->assertAchievementSet($coreSet, AchievementSetType::Core, $baseGame->id, 5, 0);

        $this->assertNotContainsAchievementSetType($resolved, AchievementSetType::Bonus);
    }

    public function testItDoesNotReturnExclusiveSetsForSpecialtyHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III', 6, 1);
        $bonusGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Bonus]', 8, 1);
        $specialtyGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Specialty]', 1, 0);
        $exclusiveGame = $this->createGameWithAchievements($this->system, 'Dragon Quest III [Subset - Exclusive]', 4, 0);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);
        $this->upsertGameCoreSetAction->execute($exclusiveGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');
        $this->associateAchievementSetToGameAction->execute($baseGame, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $user = User::factory()->create(['websitePrefs' => self::OPT_IN_TO_ALL_SUBSETS_PREF_ENABLED]);

        // Act
        $resolved = (new ResolveAchievementSetsAction())->execute($specialtyGameHash, $user);

        // Assert
        $this->assertCount(3, $resolved);

        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Core);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Bonus);
        $this->assertContainsAchievementSetType($resolved, AchievementSetType::Specialty);
        $this->assertNotContainsAchievementSetType($resolved, AchievementSetType::Exclusive);
    }
}
