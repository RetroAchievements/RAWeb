<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildActivePlayersAction;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\GameRecentPlayer;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildActivePlayersActionTest extends TestCase
{
    use RefreshDatabase;

    private function createActivePlayer(User $user, Game $game, ?string $richPresence = null): void
    {
        GameRecentPlayer::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'rich_presence' => $richPresence ?? 'Playing Stage 1',
            'rich_presence_updated_at' => now(),
        ]);
    }

    public function testItReturnsEmptyDataWhenNoActivePlayers(): void
    {
        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(0, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(0, count($result->items));
    }

    public function testItReturnsPaginatedData(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $users = User::factory()->count(5)->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        foreach ($users as $user) {
            $this->createActivePlayer($user, $game);
        }

        // Act
        $result = (new BuildActivePlayersAction())->execute(perPage: 2, page: 1);

        // Assert
        $this->assertEquals(5, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(3, $result->lastPage);
        $this->assertEquals(2, count($result->items));
    }

    public function testItSupportsFilteringPlayersByTheirLastGameId(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['ConsoleID' => $system->id]);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id]);

        $game1Users = User::factory()->count(5)->create([
            'last_game_id' => $game1->id, // !!
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        $game2Users = User::factory()->count(3)->create([
            'last_game_id' => $game2->id, // !!
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        foreach ($game1Users as $user) {
            $this->createActivePlayer($user, $game1);
        }
        foreach ($game2Users as $user) {
            $this->createActivePlayer($user, $game2);
        }

        // Act
        $result = (new BuildActivePlayersAction())->execute(gameIds: [$game1->id]);

        // Assert
        $this->assertEquals(5, $result->total);
        $this->assertEquals(5, count($result->items));

        $this->assertEquals($game1->id, $result->items[0]->game->id);
    }

    public function testItSupportsSearchesByUsername(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $user1 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'testuser123', // !!
            'rich_presence' => 'Playing game',
        ]);
        $user2 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'otheruser456', // !!
            'rich_presence' => 'Playing game',
        ]);

        $this->createActivePlayer($user1, $game, 'Playing game');
        $this->createActivePlayer($user2, $game, 'Playing game');

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'testuser');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('testuser123', $result->items[0]->user->displayName);
    }

    public function testItSupportsSearchesByRichPresenceMessage(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $user1 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'testuser123',
            'rich_presence' => 'Developing achievements',
        ]);
        $user2 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'otheruser456',
            'rich_presence' => 'Playing game',
        ]);

        $this->createActivePlayer($user1, $game, 'Developing achievements');
        $this->createActivePlayer($user2, $game, 'Playing game');

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'developing');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('testuser123', $result->items[0]->user->displayName);
        $this->assertEquals('Developing achievements', $result->items[0]->user->richPresence->resolve());
    }

    public function testItSupportsSearchesByGameTitle(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'Sonic the Hedgehog']);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'Legend of Zelda']);

        $user1 = User::factory()->create([
            'last_game_id' => $game1->id, // !!
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'Scott',
        ]);
        $user2 = User::factory()->create([
            'last_game_id' => $game2->id, // !!
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'Batman',
        ]);

        $this->createActivePlayer($user1, $game1);
        $this->createActivePlayer($user2, $game2);

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'zelda');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('Batman', $result->items[0]->user->displayName);
        $this->assertEquals($game2->id, $result->items[0]->game->id);
    }

    public function testItSupportsLogicalOrSearchOperations(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $user1 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'rich_presence' => 'Playing game',
        ]);
        $user2 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'rich_presence' => 'Developing achievements',
        ]);
        $user3 = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'rich_presence' => 'Fixing achievements',
        ]);

        $this->createActivePlayer($user1, $game, 'Playing game');
        $this->createActivePlayer($user2, $game, 'Developing achievements');
        $this->createActivePlayer($user3, $game, 'Fixing achievements');

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'developing|fixing');

        // Assert
        $this->assertEquals(2, $result->total);
    }

    public function testItExcludesInactivePlayers(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $activeUser = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        $inactiveUser = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now()->subDays(2),
            'Permissions' => Permissions::Registered,
        ]);

        // ... create recent activity for the active user ...
        $this->createActivePlayer($activeUser, $game);

        // ... create old activity for the inactive user ...
        GameRecentPlayer::factory()->create([
            'user_id' => $inactiveUser->id,
            'game_id' => $game->id,
            'rich_presence' => 'Playing Stage 1',
            'rich_presence_updated_at' => now()->subDays(2), // !! old timestamp -- edge case
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(1, $result->total);
    }

    public function testItPutsUntrackedPlayersAtBottomOfTheList(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $trackedHighPoints = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'tracked_high_points',
            'Untracked' => 0,
            'points' => 100000,
        ]);
        $untrackedHighPoints = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'untracked_high_points',
            'Untracked' => 1,
            'points' => 999999,
        ]);
        $trackedLowPoints = User::factory()->create([
            'last_game_id' => $game->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
            'username' => 'tracked_low_points',
            'Untracked' => 0,
            'points' => 50000,
        ]);

        $this->createActivePlayer($trackedHighPoints, $game);
        $this->createActivePlayer($untrackedHighPoints, $game);
        $this->createActivePlayer($trackedLowPoints, $game);

        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(3, $result->total);

        $this->assertEquals('tracked_high_points', $result->items[0]->user->displayName);
        $this->assertEquals('tracked_low_points', $result->items[1]->user->displayName);
        $this->assertEquals('untracked_high_points', $result->items[2]->user->displayName);
    }
}
