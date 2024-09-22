<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Community\Actions\AddGameToListAction;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\GameListType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class BuildGameListActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    public function testItReturnsEmptyDataForFreshUsers(): void
    {
        $user = User::factory()->create();

        $result = (new BuildGameListAction())->execute(GameListType::UserPlay, $user);

        $this->assertEquals(0, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(1, $result->lastPage);
        $this->assertEquals(25, $result->perPage);
    }

    public function testItReturnsPaginatedData(): void
    {
        // Arrange
        $user = User::factory()->create();

        $system = System::factory()->create();
        $games = Game::factory()->count(20)->create(['ConsoleID' => $system->id]);

        $addGameToListAction = new AddGameToListAction();
        foreach ($games as $game) {
            $addGameToListAction->execute($user, $game, UserGameListType::Play);
        }

        // Act
        $result = (new BuildGameListAction())->execute(GameListType::UserPlay, $user, perPage: 8);

        // Assert
        $this->assertEquals(20, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(3, $result->lastPage);
    }

    // TODO once other list contexts are supported, use a different one for this test case
    public function testItReportsGamesAreInUserBacklog(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(GameListType::UserPlay, $user);

        // Assert
        $this->assertEquals(true, $result->items[0]->isInBacklog);
        $this->assertEquals(true, $result->items[1]->isInBacklog);
        $this->assertEquals(true, $result->items[2]->isInBacklog);
        $this->assertEquals(true, $result->items[3]->isInBacklog);
        $this->assertEquals(true, $result->items[4]->isInBacklog);
        $this->assertEquals(true, $result->items[5]->isInBacklog);
    }

    public function testItSortsGameTitleByDefault(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(GameListType::UserPlay, $user);

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals('A-Mazing Tater | Puzzle Boy II', $firstItem->game->title);
        $this->assertEquals('GB', $firstItem->game->system->nameShort->resolve());

        $this->assertEquals('~Hack~ Twitch Plays Pokemon: Anniversary Red', $lastItem->game->title);
        $this->assertEquals('GB', $lastItem->game->system->nameShort->resolve());
    }

    public function testItCanSortBySystemAscending(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'system', 'direction' => 'asc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals(1000, $firstItem->game->id);
        $this->assertEquals('GB', $firstItem->game->system->nameShort->resolve());

        $this->assertEquals(1002, $lastItem->game->id);
        $this->assertEquals('NES', $lastItem->game->system->nameShort->resolve());
    }

    public function testItCanSortBySystemDescending(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'system', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals(1004, $firstItem->game->id);
        $this->assertEquals('NES', $firstItem->game->system->nameShort->resolve());

        $this->assertEquals(1001, $lastItem->game->id);
        $this->assertEquals('GB', $lastItem->game->system->nameShort->resolve());
    }

    public function testItSecondarySortsByGameTitle(): void
    {
        // Arrange
        $user = User::factory()->create();

        $systemGb = System::factory()->create(['ID' => 1, 'name' => 'Game Boy', 'name_short' => 'GB']);
        $systemNes = System::factory()->create(['ID' => 2, 'name' => 'NES/Famicom', 'name_short' => 'NES']);

        Game::factory()->create([
            'ID' => 1000,
            'ConsoleID' => $systemGb->id,
            'Title' => 'AAA',
        ]);
        Game::factory()->create([
            'ID' => 1001,
            'ConsoleID' => $systemGb->id,
            'Title' => '~Hack~ AAA',
        ]);
        Game::factory()->create([
            'ID' => 1002,
            'ConsoleID' => $systemGb->id,
            'Title' => 'ZZZ',
        ]);
        Game::factory()->create([
            'ID' => 1003,
            'ConsoleID' => $systemNes->id,
            'Title' => 'BBB',
        ]);

        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'system', 'direction' => 'asc'],
        );

        // Assert
        $this->assertEquals(1000, $result->items[0]->game->id); // "AAA" (GB)
        $this->assertEquals(1002, $result->items[1]->game->id); // "ZZZ" (GB)
        $this->assertEquals(1001, $result->items[2]->game->id); // "~Hack~ AAA" (GB)
        $this->assertEquals(1003, $result->items[3]->game->id); // "BBB" (NES)
    }

    public function testItCanSortByAchievementsPublished(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'achievementsPublished', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals(1002, $firstItem->game->id);
        $this->assertEquals(144, $firstItem->game->achievementsPublished->resolve());

        $this->assertEquals(1004, $lastItem->game->id);
        $this->assertEquals(0, $lastItem->game->achievementsPublished->resolve());
    }

    public function testItCanSortByPointsTotal(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'pointsTotal', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals(1002, $firstItem->game->id);
        $this->assertEquals(1747, $firstItem->game->pointsTotal->resolve());

        $this->assertEquals(1004, $lastItem->game->id);
        $this->assertEquals(0, $lastItem->game->pointsTotal->resolve());
    }

    public function testItCanSortByRetroRatio(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'retroRatio', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals('Final Fantasy [Subset - Solo Class]', $firstItem->game->title);
        $this->assertEquals('Cycle Race: Road Man', $lastItem->game->title);
    }

    public function testItCanSortByLastUpdated(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        Achievement::factory()->create(['GameID' => 1001, 'DateModified' => Carbon::parse('2015-06-01')]);
        Achievement::factory()->create(['GameID' => 1003, 'DateModified' => Carbon::parse('2024-03-02')]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'lastUpdated', 'direction' => 'asc'],
        );

        $firstItem = $result->items[0];
        $lastItem = $result->items[count($result->items) - 1];

        // Assert
        $this->assertEquals(1001, $firstItem->game->id);
        $this->assertEquals(Carbon::parse('2015-06-01'), $firstItem->game->lastUpdated->resolve());

        $this->assertEquals(1003, $lastItem->game->id);
        $this->assertEquals(Carbon::parse('2024-03-02'), $lastItem->game->lastUpdated->resolve());
    }

    public function testItCanSortByReleasedAt(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'releasedAt', 'direction' => 'asc'],
        );

        // Assert
        $this->assertEquals($result->items[0]->game->title, "Final Fantasy [Subset - Solo Class]");             // 1987-12-18, day
        $this->assertEquals($result->items[1]->game->title, "A-Mazing Tater | Puzzle Boy II");                  // 1991-08-02, day
        $this->assertEquals($result->items[2]->game->title, "Dragon Quest III | Dragon Warrior III");           // 1992-10-15, month
        $this->assertEquals($result->items[3]->game->title, "Double Moon Densetsu");                            // 1992-10-14, day
        $this->assertEquals($result->items[4]->game->title, "~Hack~ Twitch Plays Pokemon: Anniversary Red");    // 2015-01-01, year
        $this->assertEquals($result->items[5]->game->title, "Cycle Race: Road Man");                            // null
    }

    public function testItCanSortByNumVisibleLeaderboards(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        Leaderboard::factory()->count(3)->create(['GameID' => 1004, 'DisplayOrder' => 1]);
        Leaderboard::factory()->count(5)->create(['GameID' => 1005, 'DisplayOrder' => -1]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'numVisibleLeaderboards', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];

        // Assert
        $this->assertEquals(1004, $firstItem->game->id);
        $this->assertEquals(3, $firstItem->game->numVisibleLeaderboards->resolve());
    }

    public function testItCanSortByNumUnresolvedTickets(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $achievement1001 = Achievement::factory()->create(['GameID' => 1001, 'Flags' => AchievementFlag::OfficialCore]);
        $achievement1003 = Achievement::factory()->create(['GameID' => 1003, 'Flags' => AchievementFlag::OfficialCore]);

        Ticket::factory()->count(3)->create(['AchievementID' => $achievement1001->id, 'ReportState' => TicketState::Open]);
        Ticket::factory()->count(27)->create(['AchievementID' => $achievement1003->id, 'ReportState' => TicketState::Closed]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'numUnresolvedTickets', 'direction' => 'desc'],
        );

        $firstItem = $result->items[0];

        // Assert
        $this->assertEquals(1001, $firstItem->game->id);
        $this->assertEquals(3, $firstItem->game->numUnresolvedTickets->resolve());
    }

    public function testItCanSortByProgress(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => 1002, 'completion_percentage' => 0.55]);
        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => 1001, 'completion_percentage' => 0.45]);
        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => 1004, 'completion_percentage' => null]);
        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => 1005, 'completion_percentage' => 0.35]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            sort: ['field' => 'progress', 'direction' => 'desc'],
        );

        // Assert
        $this->assertEquals(1002, $result->items[0]->game->id);
        $this->assertEquals(1001, $result->items[1]->game->id);
        $this->assertEquals(1005, $result->items[2]->game->id);
    }

    public function testItCanFilterByOneSystem(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['system' => [1]],
        );

        // Assert
        $this->assertEquals(2, $result->total);
        $this->assertEquals(2, count($result->items));
    }

    public function testItCanFilterByMultipleSystems(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['system' => [1, 2]],
        );

        // Assert
        $this->assertEquals(6, $result->total);
        $this->assertEquals(6, count($result->items));
    }

    public function testItCanFilterByHavingSomeAchievementsPublished(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['hasAchievementsPublished' => ['has']]
        );

        // Assert
        $this->assertEquals(5, $result->total);
        $this->assertEquals(5, count($result->items));
    }

    public function testItCanFilterByHavingNoAchievementsPublished(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['hasAchievementsPublished' => ['none']]
        );

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals(1, count($result->items));
    }

    public function testItCanFilterByUnfinishedAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Hardcore);

        $this->addGameBeatenAward($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['unfinished']],
        );

        // Assert
        $this->assertEquals(4, $result->total);
        $this->assertEquals(4, count($result->items));
    }

    public function testItCanFilterByFinishedAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['finished']],
        );

        // Assert
        $this->assertEquals(4, $result->total);
        $this->assertEquals(4, count($result->items)); // These values can differ unless we override ->total.
    }

    public function testItCanFilterByBeatenSoftcoreAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['beaten_softcore']],
        );

        // Assert
        $this->assertEquals(2, $result->total);
        $this->assertEquals(2, count($result->items)); // These values can differ unless we override ->total.
    }

    public function testItCanFilterByBeatenHardcoreAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore);      // note, because 1001 is also mastered, it'll be ignored.
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1004), UnlockMode::Hardcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['beaten_hardcore']],
        );

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals(1, count($result->items)); // These values can differ unless we override ->total.
    }

    public function testItCanFilterByCompletedAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1004), UnlockMode::Hardcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['completed']],
        );

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals(1, count($result->items)); // These values can differ unless we override ->total.
    }

    public function testItCanFilterByMasteredAwardCategory(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Softcore); // they completed it first...
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore); // then they mastered it later
        $this->addGameBeatenAward($user, Game::find(1002), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Hardcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1004), UnlockMode::Hardcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['mastered']],
        );

        // Assert
        $this->assertEquals(2, $result->total);
        $this->assertEquals(2, count($result->items)); // These values can differ unless we override ->total.
    }

    public function testItCanFilterByMultipleAwardCategories(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->seedGamesForLists();
        $this->addGameIdsToUserPlayList($user, gameIds: [1000, 1001, 1002, 1003, 1004, 1005]);

        $this->addGameBeatenAward($user, Game::find(1000), UnlockMode::Softcore);
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Softcore); // they completed it first...
        $this->addMasteryBadge($user, Game::find(1001), UnlockMode::Hardcore); // then they mastered it later
        $this->addGameBeatenAward($user, Game::find(1002), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1002), UnlockMode::Hardcore);
        $this->addGameBeatenAward($user, Game::find(1003), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, Game::find(1004), UnlockMode::Hardcore);
        $this->addMasteryBadge($user, Game::find(1005), UnlockMode::Softcore);

        // Act
        $result = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            $user,
            filters: ['award' => ['completed', 'mastered']],
        );

        // Assert
        $this->assertEquals(3, $result->total);
        $this->assertEquals(3, count($result->items)); // These values can differ unless we override ->total.
    }

    private function seedGamesForLists(): void
    {
        $systemGb = System::factory()->create(['ID' => 1, 'name' => 'Game Boy', 'name_short' => 'GB']);
        $systemNes = System::factory()->create(['ID' => 2, 'name' => 'NES/Famicom', 'name_short' => 'NES']);

        Game::factory()->create([
            'ID' => 1000,
            'ConsoleID' => $systemGb->id,
            'Title' => 'A-Mazing Tater | Puzzle Boy II',
            'ImageIcon' => '/Images/090884.png',
            'released_at' => '1991-08-02 00:55:29',
            'released_at_granularity' => 'day',
            'players_total' => 969,
            'players_hardcore' => 641,
            'achievements_published' => 45,
            'points_total' => 448,
            'TotalTruePoints' => 813,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);

        Game::factory()->create([
            'ID' => 1001,
            'ConsoleID' => $systemGb->id,
            'Title' => '~Hack~ Twitch Plays Pokemon: Anniversary Red',
            'ImageIcon' => '/Images/094381.png',
            'released_at' => '2015-01-01 00:00:00',
            'released_at_granularity' => 'year',
            'players_total' => 120,
            'players_hardcore' => 97,
            'achievements_published' => 47,
            'points_total' => 300,
            'TotalTruePoints' => 363,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);

        Game::factory()->create([
            'ID' => 1002,
            'ConsoleID' => $systemNes->id,
            'Title' => 'Final Fantasy [Subset - Solo Class]',
            'ImageIcon' => '/Images/071115.png',
            'released_at' => '1987-12-18 00:55:31',
            'released_at_granularity' => 'day',
            'players_total' => 2856,
            'players_hardcore' => 2054,
            'achievements_published' => 144,
            'points_total' => 1747,
            'TotalTruePoints' => 35962,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);

        Game::factory()->create([
            'ID' => 1003,
            'ConsoleID' => $systemNes->id,
            'Title' => 'Dragon Quest III | Dragon Warrior III',
            'ImageIcon' => '/Images/026797.png',
            'released_at' => '1992-10-15 00:00:00',
            'released_at_granularity' => 'month',
            'players_total' => 826,
            'players_hardcore' => 623,
            'achievements_published' => 50,
            'points_total' => 400,
            'TotalTruePoints' => 548,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);

        Game::factory()->create([
            'ID' => 1004,
            'ConsoleID' => $systemNes->id,
            'Title' => 'Cycle Race: Road Man',
            'ImageIcon' => '/Images/013746.png',
            'released_at' => null,
            'released_at_granularity' => null,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'points_total' => 0,
            'TotalTruePoints' => 0,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);

        Game::factory()->create([
            'ID' => 1005,
            'ConsoleID' => $systemNes->id,
            'Title' => 'Double Moon Densetsu',
            'ImageIcon' => '/Images/071237.png',
            'released_at' => '1992-10-14 00:00:00',
            'released_at_granularity' => 'day',
            'players_total' => 18,
            'players_hardcore' => 17,
            'achievements_published' => 38,
            'points_total' => 240,
            'TotalTruePoints' => 282,
            'Updated' => Carbon::parse('2023-06-06'),
        ]);
    }

    private function addGameIdsToUserPlayList(User $user, array $gameIds): void
    {
        $addGameToListAction = new AddGameToListAction();
        foreach ($gameIds as $gameId) {
            $addGameToListAction->execute($user, Game::find($gameId), UserGameListType::Play);
        }
    }
}
