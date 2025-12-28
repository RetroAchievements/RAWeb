<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class GameInfoAndUserProgressTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testGetGameInfoAndUserProgressUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => 999999, 'u' => $this->user->User]))
            ->assertSuccessful()
            ->assertExactJson([]);
    }

    public function testGetGameInfoAndUserProgressByName(): void
    {
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ForumTopicID' => 1234,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->ID,
            'title' => $game->Title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();

        // $this->user has all achievements unlocked in hardcore
        $this->addHardcoreUnlock($this->user, $achievement1);
        $this->addHardcoreUnlock($this->user, $achievement2);
        $this->addHardcoreUnlock($this->user, $achievement3);

        // user2 has all achievements unlocked in softcore
        $this->addSoftcoreUnlock($user2, $achievement1);
        $this->addSoftcoreUnlock($user2, $achievement2);
        $this->addSoftcoreUnlock($user2, $achievement3);

        // user3 has all achievements unlocked, mix of hardcore and softcore
        $this->addHardcoreUnlock($user3, $achievement1);
        $this->addSoftcoreUnlock($user3, $achievement2);
        $this->addSoftcoreUnlock($user3, $achievement3);

        // user4 only has two achievements unlocked
        $this->addHardcoreUnlock($user4, $achievement1);
        $this->addHardcoreUnlock($user4, $achievement2);

        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user3->User])) // !! name
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '33.33%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 3,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
            ]);
    }

    public function testGetGameInfoAndUserProgressByUlid(): void
    {
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ForumTopicID' => 1234,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->ID,
            'title' => $game->Title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();

        // $this->user has all achievements unlocked in hardcore
        $this->addHardcoreUnlock($this->user, $achievement1);
        $this->addHardcoreUnlock($this->user, $achievement2);
        $this->addHardcoreUnlock($this->user, $achievement3);

        // user2 has all achievements unlocked in softcore
        $this->addSoftcoreUnlock($user2, $achievement1);
        $this->addSoftcoreUnlock($user2, $achievement2);
        $this->addSoftcoreUnlock($user2, $achievement3);

        // user3 has all achievements unlocked, mix of hardcore and softcore
        $this->addHardcoreUnlock($user3, $achievement1);
        $this->addSoftcoreUnlock($user3, $achievement2);
        $this->addSoftcoreUnlock($user3, $achievement3);

        // user4 only has two achievements unlocked
        $this->addHardcoreUnlock($user4, $achievement1);
        $this->addHardcoreUnlock($user4, $achievement2);

        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user3->ulid])) // !! ulid
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '33.33%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 3,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
            ]);
    }

    public function testGetGameInfoAndUserProgressNoAchievements(): void
    {
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ForumTopicID' => 1234,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->ID,
            'title' => $game->Title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        // issue #484: empty associative array should still return {}, not []
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 0,
                'NumDistinctPlayers' => 0,
                'NumDistinctPlayersCasual' => 0,
                'NumDistinctPlayersHardcore' => 0,
                'NumAwardedToUser' => 0,
                'NumAwardedToUserHardcore' => 0,
                'UserCompletion' => '0.00%',
                'UserCompletionHardcore' => '0.00%',
                'UserTotalPlaytime' => 0,
            ])
            ->assertSee('"Achievements":{}', false);
    }

    public function testGetGameInfoAndUserProgressWithHighestAwardMetadata(): void
    {
        Carbon::setTestNow(Carbon::now());

        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ForumTopicID' => 1234,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->ID,
            'title' => $game->Title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1, 'type' => AchievementType::Progression]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();

        // $this->user has all achievements unlocked in hardcore and a mastery award
        $this->addHardcoreUnlock($this->user, $achievement1);
        $this->addHardcoreUnlock($this->user, $achievement2);
        $this->addHardcoreUnlock($this->user, $achievement3);
        $this->addMasteryBadge($this->user, $game, UnlockMode::Hardcore);

        // user2 has all achievements unlocked in softcore and a completion award
        $this->addSoftcoreUnlock($user2, $achievement1);
        $this->addSoftcoreUnlock($user2, $achievement2);
        $this->addSoftcoreUnlock($user2, $achievement3);
        $this->addMasteryBadge($user2, $game, UnlockMode::Softcore);

        // user3 has only one achievement unlocked and a beaten (hardcore) award
        $this->addHardcoreUnlock($user3, $achievement1);
        $this->addGameBeatenAward($user3, $game, UnlockMode::Hardcore);

        // user4 has only one achievement unlocked and no award
        $this->addHardcoreUnlock($user4, $achievement2);

        // make the API call for $this->user
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $this->user->User, 'a' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 3,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '100.00%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
                'HighestAwardKind' => 'mastered',
                'HighestAwardDate' => Carbon::now()->toIso8601String(),
            ]);

        // make the API call for user2
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user2->User, 'a' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 0,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '0.00%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
                'HighestAwardKind' => 'completed',
                'HighestAwardDate' => Carbon::now()->toIso8601String(),
            ]);

        // make the API call for user3
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user3->User, 'a' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 1,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '33.33%',
                'UserCompletionHardcore' => '33.33%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
                'HighestAwardKind' => 'beaten-hardcore',
                'HighestAwardDate' => Carbon::now()->toIso8601String(),
            ]);

        // make the API call for user4
        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user4->User, 'a' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 1,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '33.33%',
                'UserCompletionHardcore' => '33.33%',
                'UserTotalPlaytime' => 60,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->User,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->User,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->User,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
                'HighestAwardKind' => null,
                'HighestAwardDate' => null,
            ]);
    }
}
