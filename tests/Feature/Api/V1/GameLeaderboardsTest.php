<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\ValueFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameLeaderboardsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testGetGameLeaderboardsGameWithNoLeaderboards(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetGameLeaderboards(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a game with 5 leaderboards: */

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 1",
            'description' => "I am the first leaderboard",
        ]);
        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $userOne->id,
            'score' => 1,
        ]);
        $untrackedUser = User::factory()->create(['User' => 'cheater', "unranked_at" => Carbon::now(), "Untracked" => 1]);
        $untrackedLeaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $untrackedUser->id,
            'score' => 2,
        ]);

        $untrackedUser2 = User::factory()->create(['User' => 'cheater2', "unranked_at" => Carbon::now(), "Untracked" => 1]);
        $untrackedLeaderboardEntry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $untrackedUser2->id,
            'score' => 4,
        ]);

        $deletedEntryUser = User::factory()->create(['User' => 'deletedEntryUse']);
        $untrackedLeaderboardEntry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $deletedEntryUser->id,
            'score' => 3,
            'deleted_at' => Carbon::now()->subDay(),
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 2",
            'description' => "I am the second leaderboard",
        ]);
        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
            'user_id' => $userTwo->id,
            'score' => 1,
        ]);
        $bannedUser = User::factory()->create(['User' => 'bannedUser', "banned_at" => Carbon::now(), 'unranked_at' => Carbon::now()]);
        $bannedLeaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
            'user_id' => $bannedUser->id,
            'score' => 2,
        ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 3",
            'description' => "I am the third leaderboard",
        ]);
        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->id,
            'user_id' => $userThree->id,
            'score' => 1,
        ]);
        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->id,
            'user_id' => $userFour->id,
            'score' => 2,
        ]);

        /** @var Leaderboard $leaderboardFour */
        $leaderboardFour = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 4",
            'description' => "I am the fourth leaderboard",
        ]);

        /** @var Leaderboard $leaderboardFive */
        $leaderboardFive = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 5",
            'description' => "I am the fifth leaderboard",
            'format' => "TIME",
            'rank_asc' => 1,
        ]);
        $userFive = User::factory()->create(['User' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->id,
            'user_id' => $userFive->id,
            'score' => 2,
        ]);
        $userSix = User::factory()->create(['User' => 'myUser6']);
        $leaderboardEntrySix = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->id,
            'user_id' => $userSix->id,
            'score' => 1,
        ]);
        $userSeven = User::factory()->create(['User' => 'myUser7']);
        $leaderboardEntrySeven = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->id,
            'user_id' => $userSeven->id,
            'score' => 3,
        ]);

        /** @var Leaderboard $hiddenLeaderboard */
        $hiddenLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test hidden leaderboard",
            'description' => "I am a hidden leaderboard",
            'format' => "TIME",
            'rank_asc' => 1,
            'order_column' => -1,
        ]);
        $userEight = User::factory()->create(['User' => 'myUser8']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $hiddenLeaderboard->id,
            'user_id' => $userEight->id,
            'score' => 2,
        ]);

        // Force recalculation of denormalized data in leaderboards.
        $action = new RecalculateLeaderboardTopEntryAction();
        $action->execute($leaderboardOne->id);
        $action->execute($leaderboardTwo->id);
        $action->execute($leaderboardThree->id);
        $action->execute($leaderboardFour->id);
        $action->execute($leaderboardFive->id);

        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "ID" => $leaderboardOne->id,
                        "RankAsc" => boolval($leaderboardOne->rank_asc),
                        "Title" => $leaderboardOne->title,
                        "Description" => $leaderboardOne->description,
                        "Format" => $leaderboardOne->format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryOne->User->User,
                            "ULID" => $leaderboardEntryOne->User->ulid,
                            "Score" => $leaderboardEntryOne->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryOne->score, $leaderboardOne->format),
                        ],
                        "Author" => $leaderboardOne->developer->display_name,
                        "AuthorULID" => $leaderboardOne->developer->ulid,
                        "State" => LeaderboardState::Active->value,
                    ],
                    [
                        "ID" => $leaderboardTwo->id,
                        "RankAsc" => boolval($leaderboardTwo->rank_asc),
                        "Title" => $leaderboardTwo->title,
                        "Description" => $leaderboardTwo->description,
                        "Format" => $leaderboardTwo->format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryTwo->User->User,
                            "ULID" => $leaderboardEntryTwo->User->ulid,
                            "Score" => $leaderboardEntryTwo->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->format),
                        ],
                        "Author" => $leaderboardTwo->developer->display_name,
                        "AuthorULID" => $leaderboardTwo->developer->ulid,
                        "State" => LeaderboardState::Active->value,
                    ],
                    [
                        "ID" => $leaderboardThree->id,
                        "RankAsc" => boolval($leaderboardThree->rank_asc),
                        "Title" => $leaderboardThree->title,
                        "Description" => $leaderboardThree->description,
                        "Format" => $leaderboardThree->format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryFour->User->User,
                            "ULID" => $leaderboardEntryFour->User->ulid,
                            "Score" => $leaderboardEntryFour->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardThree->format),
                        ],
                        "Author" => $leaderboardThree->developer->display_name,
                        "AuthorULID" => $leaderboardThree->developer->ulid,
                        "State" => LeaderboardState::Active->value,
                    ],
                    [
                        "ID" => $leaderboardFour->id,
                        "RankAsc" => boolval($leaderboardFour->rank_asc),
                        "Title" => $leaderboardFour->title,
                        "Description" => $leaderboardFour->description,
                        "Format" => $leaderboardFour->format,
                        "TopEntry" => [],
                        "Author" => $leaderboardFour->developer->display_name,
                        "AuthorULID" => $leaderboardFour->developer->ulid,
                        "State" => LeaderboardState::Active->value,
                    ],
                    [
                        "ID" => $leaderboardFive->id,
                        "RankAsc" => boolval($leaderboardFive->rank_asc),
                        "Title" => $leaderboardFive->title,
                        "Description" => $leaderboardFive->description,
                        "Format" => $leaderboardFive->format,
                        "TopEntry" => [
                            "User" => $leaderboardEntrySix->User->User,
                            "ULID" => $leaderboardEntrySix->User->ulid,
                            "Score" => $leaderboardEntrySix->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntrySix->score, $leaderboardFive->format),
                        ],
                        "Author" => $leaderboardFive->developer->display_name,
                        "AuthorULID" => $leaderboardFive->developer->ulid,
                        "State" => LeaderboardState::Active->value,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardFour->id,
                            "RankAsc" => boolval($leaderboardFour->rank_asc),
                            "Title" => $leaderboardFour->title,
                            "Description" => $leaderboardFour->description,
                            "Format" => $leaderboardFour->format,
                            "TopEntry" => [],
                            "Author" => $leaderboardFour->developer->display_name,
                            "AuthorULID" => $leaderboardFour->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                        [
                            "ID" => $leaderboardFive->id,
                            "RankAsc" => boolval($leaderboardFive->rank_asc),
                            "Title" => $leaderboardFive->title,
                            "Description" => $leaderboardFive->description,
                            "Format" => $leaderboardFive->format,
                            "TopEntry" => [
                                "User" => $leaderboardEntrySix->User->User,
                                "ULID" => $leaderboardEntrySix->User->ulid,
                                "Score" => $leaderboardEntrySix->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntrySix->score, $leaderboardFive->format),
                            ],
                            "Author" => $leaderboardFive->developer->display_name,
                            "AuthorULID" => $leaderboardFive->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardOne->id,
                            "RankAsc" => boolval($leaderboardOne->rank_asc),
                            "Title" => $leaderboardOne->title,
                            "Description" => $leaderboardOne->description,
                            "Format" => $leaderboardOne->format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryOne->User->User,
                                "ULID" => $leaderboardEntryOne->User->ulid,
                                "Score" => $leaderboardEntryOne->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryOne->score, $leaderboardOne->format),
                            ],
                            "Author" => $leaderboardOne->developer->display_name,
                            "AuthorULID" => $leaderboardOne->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                        [
                            "ID" => $leaderboardTwo->id,
                            "RankAsc" => boolval($leaderboardTwo->rank_asc),
                            "Title" => $leaderboardTwo->title,
                            "Description" => $leaderboardTwo->description,
                            "Format" => $leaderboardTwo->format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryTwo->User->User,
                                "ULID" => $leaderboardEntryTwo->User->ulid,
                                "Score" => $leaderboardEntryTwo->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->format),
                            ],
                            "Author" => $leaderboardTwo->developer->display_name,
                            "AuthorULID" => $leaderboardTwo->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardTwo->id,
                            "RankAsc" => boolval($leaderboardTwo->rank_asc),
                            "Title" => $leaderboardTwo->title,
                            "Description" => $leaderboardTwo->description,
                            "Format" => $leaderboardTwo->format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryTwo->User->User,
                                "ULID" => $leaderboardEntryTwo->User->ulid,
                                "Score" => $leaderboardEntryTwo->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->format),
                            ],
                            "Author" => $leaderboardTwo->developer->display_name,
                            "AuthorULID" => $leaderboardTwo->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                        [
                            "ID" => $leaderboardThree->id,
                            "RankAsc" => boolval($leaderboardThree->rank_asc),
                            "Title" => $leaderboardThree->title,
                            "Description" => $leaderboardThree->description,
                            "Format" => $leaderboardThree->format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryFour->User->User,
                                "ULID" => $leaderboardEntryFour->User->ulid,
                                "Score" => $leaderboardEntryFour->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardThree->format),
                            ],
                            "Author" => $leaderboardThree->developer->display_name,
                            "AuthorULID" => $leaderboardThree->developer->ulid,
                            "State" => LeaderboardState::Active->value,
                        ],
                    ],
                ]);
    }

    public function testGetLeaderboardEntriesReturnsCorrectState(): void
    {
        // Setup leaderboards with different states

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $activeLeaderboard */
        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Active Leaderboard ",
            'description' => "I am an active leaderboard",
            'state' => LeaderboardState::Active,
        ]);

        /** @var Leaderboard $disabledLeaderboard */
        $disabledLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Disabled Leaderboard ",
            'description' => "I am a disabled leaderboard",
            'state' => LeaderboardState::Disabled,
        ]);

        /** @var Leaderboard $unpublishedLeaderboard */
        $unpublishedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Unpublished Leaderboard ",
            'description' => "I am an unpublished leaderboard",
            'state' => LeaderboardState::Unpublished,
        ]);

        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 3,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $activeLeaderboard->id,
                        'State' => LeaderboardState::Active->value,
                    ],
                    [
                        'ID' => $disabledLeaderboard->id,
                        'State' => LeaderboardState::Disabled->value,
                    ],
                    [
                        'ID' => $unpublishedLeaderboard->id,
                        'State' => LeaderboardState::Unpublished->value,
                    ],
                ],
            ]);
    }
}
