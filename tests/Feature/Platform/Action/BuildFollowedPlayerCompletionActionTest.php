<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Models\UserRelation;
use App\Platform\Actions\BuildFollowedPlayerCompletionAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildFollowedPlayerCompletionActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyCollectionWhenUserIsNull(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute(null, $game);

        // Assert
        $this->assertCount(0, $result);
    }

    public function testItReturnsEmptyCollectionWhenUserHasNoFollowedPlayers(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $user = User::factory()->create();

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute($user, $game);

        // Assert
        $this->assertCount(0, $result);
    }

    public function testItReturnsEmptyCollectionWhenFollowedPlayersHaveNoGameProgress(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $user = User::factory()->create();
        $followedUser = User::factory()->create();

        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $followedUser->id,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $followedUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 0,
            'achievements_unlocked_hardcore' => 0,
        ]);

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute($user, $game);

        // Assert
        $this->assertCount(0, $result);
    }

    public function testItReturnsFollowedPlayersWithUnlockedAchievements(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $user = User::factory()->create();
        $followedUser = User::factory()->create();

        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $followedUser->id,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $followedUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 5,
            'achievements_unlocked_hardcore' => 3,
            'achievements_unlocked_softcore' => 2,
            'achievements_total' => 10,
            'points' => 50,
            'points_hardcore' => 30,
            'points_total' => 100,
        ]);

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute($user, $game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($followedUser->id, $result->first()->user->id->resolve());
        $this->assertEquals(5, $result->first()->playerGame->achievementsUnlocked);
        $this->assertEquals(3, $result->first()->playerGame->achievementsUnlockedHardcore);
        $this->assertEquals(2, $result->first()->playerGame->achievementsUnlockedSoftcore);
        $this->assertEquals(50, $result->first()->playerGame->points);
        $this->assertEquals(30, $result->first()->playerGame->pointsHardcore);
    }

    public function testItSortsFollowedPlayersByHardcoreUnlocksThenSoftcoreUnlocks(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $user = User::factory()->create();

        // ... create three followed users with different unlock patterns ...
        $user1 = User::factory()->create(['User' => 'User1']); // will have most hardcore unlocks
        $user2 = User::factory()->create(['User' => 'User2']); // will have second-most hardcore, but most total
        $user3 = User::factory()->create(['User' => 'User3']); // will have least unlocks overall

        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $user1->id,
        ]);
        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $user2->id,
        ]);
        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $user3->id,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user1->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 8,
            'achievements_unlocked_hardcore' => 7, // !! most hardcore
            'achievements_unlocked_softcore' => 1,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user2->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 10, // !! most total
            'achievements_unlocked_hardcore' => 5,
            'achievements_unlocked_softcore' => 5,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user3->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 3,
            'achievements_unlocked_hardcore' => 2,
            'achievements_unlocked_softcore' => 1,
        ]);

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute($user, $game);

        // Assert
        $this->assertCount(3, $result);

        // ... user1 (most hardcore) ...
        $this->assertEquals($user1->id, $result[0]->user->id->resolve());
        $this->assertEquals(7, $result[0]->playerGame->achievementsUnlockedHardcore);

        // ... user2 (second most hardcore) ...
        $this->assertEquals($user2->id, $result[1]->user->id->resolve());
        $this->assertEquals(5, $result[1]->playerGame->achievementsUnlockedHardcore);

        // ... user3 (least hardcore) ...
        $this->assertEquals($user3->id, $result[2]->user->id->resolve());
        $this->assertEquals(2, $result[2]->playerGame->achievementsUnlockedHardcore);
    }

    public function testItOnlyIncludesFollowingRelationshipsNotOtherTypes(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $user = User::factory()->create();

        $followedUser = User::factory()->create();
        $blockedUser = User::factory()->create();

        UserRelation::factory()->following()->create([
            'user_id' => $user->id,
            'related_user_id' => $followedUser->id,
        ]);
        UserRelation::factory()->blocked()->create([
            'user_id' => $user->id,
            'related_user_id' => $blockedUser->id,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $followedUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 5,
            'achievements_unlocked_hardcore' => 3,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $blockedUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 8,
            'achievements_unlocked_hardcore' => 6,
        ]);

        $action = new BuildFollowedPlayerCompletionAction();

        // Act
        $result = $action->execute($user, $game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($followedUser->id, $result->first()->user->id->resolve());

        // ... ensure the blocked user is not included ...
        $this->assertFalse($result->contains(fn ($item) => $item->user->id->resolve() === $blockedUser->id));
    }
}
