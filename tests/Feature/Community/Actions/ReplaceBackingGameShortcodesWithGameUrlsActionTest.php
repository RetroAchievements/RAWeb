<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\ReplaceBackingGameShortcodesWithGameUrlsAction;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplaceBackingGameShortcodesWithGameUrlsActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItConvertsBackingGameWithExactlyOneParent(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create(['id' => 9534]);
        $parentGame = Game::factory()->create(['id' => 668]);
        $backingGame = Game::factory()->create(['id' => 29895]);

        // ... parent game has the set as Bonus ...
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Bonus, // !!
        ]);

        // ... backing game has the set as Core ...
        GameAchievementSet::factory()->create([
            'game_id' => $backingGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core, // !! core type makes it a backing game
        ]);

        $messageBody = 'Check out [game=29895]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $expectedUrl = route('game.show', ['game' => 668, 'set' => 9534]); // full URL
        $this->assertEquals("Check out {$expectedUrl}!", $result);
    }

    public function testItDoesNotConvertBackingGameWithMultipleParents(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create(['id' => 9534]);
        $parentGame1 = Game::factory()->create(['id' => 668]);
        $parentGame2 = Game::factory()->create(['id' => 700]);
        $backingGame = Game::factory()->create(['id' => 29895]);

        // ... both parent games have the set as Bonus ...
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame1->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Bonus, // !!
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame2->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Bonus, // !!
        ]);

        // ... backing game has the set as Core ...
        GameAchievementSet::factory()->create([
            'game_id' => $backingGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core, // !!
        ]);

        $messageBody = 'Check out [game=29895]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('Check out [game=29895]!', $result); // unchanged due to multiple parents
    }

    public function testItDoesNotConvertNormalGameShortcodes(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create(['id' => 1]);
        $normalGame = Game::factory()->create(['id' => 668]);

        // ... normal game has set as Core (but no other game uses this set) ...
        GameAchievementSet::factory()->create([
            'game_id' => $normalGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core, // !!
        ]);

        $messageBody = 'Check out [game=668]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('Check out [game=668]!', $result); // unchanged since no parent exists
    }

    public function testItHandlesMixedContentCorrectly(): void
    {
        // Arrange
        $set1 = AchievementSet::factory()->create(['id' => 9534]);
        $set2 = AchievementSet::factory()->create(['id' => 100]);

        $parentGame1 = Game::factory()->create(['id' => 668]);
        $backingGame1 = Game::factory()->create(['id' => 29895]);
        $normalGame = Game::factory()->create(['id' => 500]);

        // ... set up backing game 1 with one parent ...
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame1->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Bonus, // !! parent
        ]);

        GameAchievementSet::factory()->create([
            'game_id' => $backingGame1->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Core, // !! backing game
        ]);

        // ... normal game has its own Core set ...
        GameAchievementSet::factory()->create([
            'game_id' => $normalGame->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Core, // !! no parent for this
        ]);

        $messageBody = 'Play [game=668] or [game=29895] or [game=500]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $expectedUrl = route('game.show', ['game' => 668, 'set' => 9534]);
        $this->assertEquals("Play [game=668] or {$expectedUrl} or [game=500]!", $result); // !! only backing game converted
    }

    public function testItHandlesMultipleBackingGamesInSameMessage(): void
    {
        // Arrange
        $set1 = AchievementSet::factory()->create(['id' => 9534]);
        $set2 = AchievementSet::factory()->create(['id' => 8659]);

        $parentGame1 = Game::factory()->create(['id' => 668]);
        $backingGame1 = Game::factory()->create(['id' => 29895]);

        $parentGame2 = Game::factory()->create(['id' => 1]);
        $backingGame2 = Game::factory()->create(['id' => 28000]);

        // ... set up backing game 1 ...
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame1->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Bonus, // !! parent
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $backingGame1->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Core, // !! backing
        ]);

        // ... set up backing game 2 ...
        GameAchievementSet::factory()->create([
            'game_id' => $parentGame2->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Specialty, // !! parent
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $backingGame2->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Core, // !! backing
        ]);

        $messageBody = 'Try [game=29895] and [game=28000]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $expectedUrl1 = route('game.show', ['game' => 668, 'set' => 9534]);
        $expectedUrl2 = route('game.show', ['game' => 1, 'set' => 8659]);
        $this->assertEquals("Try {$expectedUrl1} and {$expectedUrl2}!", $result); // !! both converted
    }

    public function testItHandlesEmptyMessageBody(): void
    {
        // Arrange
        $messageBody = '';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('', $result);
    }

    public function testItHandlesMessageWithNoGameShortcodes(): void
    {
        // Arrange
        $messageBody = 'This is just a normal message with no shortcodes.';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('This is just a normal message with no shortcodes.', $result);
    }

    public function testItHandlesBackingGameWithNoParentGames(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create(['id' => 9534]);
        $backingGame = Game::factory()->create(['id' => 29895]);

        // ... backing game has the set as Core, but no parent games use it ...
        GameAchievementSet::factory()->create([
            'game_id' => $backingGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core, // !! orphaned
        ]);

        $messageBody = 'Check out [game=29895]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('Check out [game=29895]!', $result); // unchanged due to no parent
    }

    public function testItHandlesGameWithNoCoreSet(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create(['id' => 9534]);
        $game = Game::factory()->create(['id' => 668]);

        // ... game has the set as Bonus, not Core ...
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Bonus, // !! not a backing game
        ]);

        $messageBody = 'Check out [game=668]!';

        // Act
        $result = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($messageBody);

        // Assert
        $this->assertEquals('Check out [game=668]!', $result); // unchanged since not a backing game
    }
}
