<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementType;
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

    public function testGetGameInfoAndUserProgress(): void
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
            'Released' => 'Jan 1989',
            'released_at' => $releasedAt,
            'released_at_granularity' => 'day',
        ]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '12345', 'DisplayOrder' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '23456', 'DisplayOrder' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '34567', 'DisplayOrder' => 2]);

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

        $this->get($this->apiUrl('GetGameInfoAndUserProgress', ['g' => $game->ID, 'u' => $user3->User]))
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
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '33.33%',
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->developer->User,
                        'DateCreated' => $achievement1->DateCreated->__toString(),
                        'DateModified' => $achievement1->DateModified->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 3,
                    ],
                    $achievement3->ID => [
                        'ID' => $achievement3->ID,
                        'Title' => $achievement3->Title,
                        'Description' => $achievement3->Description,
                        'Points' => $achievement3->Points,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->developer->User,
                        'DateCreated' => $achievement3->DateCreated->__toString(),
                        'DateModified' => $achievement3->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->ID => [
                        'ID' => $achievement2->ID,
                        'Title' => $achievement2->Title,
                        'Description' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->developer->User,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
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
            'Released' => 'Jan 1989',
            'released_at' => $releasedAt,
            'released_at_granularity' => 'day',
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
                'IsFinal' => 0,
                'NumAchievements' => 0,
                'NumDistinctPlayers' => 0,
                'NumDistinctPlayersCasual' => 0,
                'NumDistinctPlayersHardcore' => 0,
                'NumAwardedToUser' => 0,
                'NumAwardedToUserHardcore' => 0,
                'UserCompletion' => '0.00%',
                'UserCompletionHardcore' => '0.00%',
            ])
            ->assertSee('"Achievements":{},', false);
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
            'Released' => 'Jan 1989',
            'released_at' => $releasedAt,
            'released_at_granularity' => 'day',
        ]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '12345', 'DisplayOrder' => 1, 'type' => AchievementType::Progression]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '23456', 'DisplayOrder' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '34567', 'DisplayOrder' => 2]);

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
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 3,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '100.00%',
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->developer->User,
                        'DateCreated' => $achievement1->DateCreated->__toString(),
                        'DateModified' => $achievement1->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->ID => [
                        'ID' => $achievement3->ID,
                        'Title' => $achievement3->Title,
                        'Description' => $achievement3->Description,
                        'Points' => $achievement3->Points,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->developer->User,
                        'DateCreated' => $achievement3->DateCreated->__toString(),
                        'DateModified' => $achievement3->DateModified->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->ID => [
                        'ID' => $achievement2->ID,
                        'Title' => $achievement2->Title,
                        'Description' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->developer->User,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
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
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 3,
                'NumAwardedToUserHardcore' => 0,
                'UserCompletion' => '100.00%',
                'UserCompletionHardcore' => '0.00%',
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->developer->User,
                        'DateCreated' => $achievement1->DateCreated->__toString(),
                        'DateModified' => $achievement1->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->ID => [
                        'ID' => $achievement3->ID,
                        'Title' => $achievement3->Title,
                        'Description' => $achievement3->Description,
                        'Points' => $achievement3->Points,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->developer->User,
                        'DateCreated' => $achievement3->DateCreated->__toString(),
                        'DateModified' => $achievement3->DateModified->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->ID => [
                        'ID' => $achievement2->ID,
                        'Title' => $achievement2->Title,
                        'Description' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->developer->User,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
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
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 1,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '33.33%',
                'UserCompletionHardcore' => '33.33%',
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->developer->User,
                        'DateCreated' => $achievement1->DateCreated->__toString(),
                        'DateModified' => $achievement1->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->ID => [
                        'ID' => $achievement3->ID,
                        'Title' => $achievement3->Title,
                        'Description' => $achievement3->Description,
                        'Points' => $achievement3->Points,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->developer->User,
                        'DateCreated' => $achievement3->DateCreated->__toString(),
                        'DateModified' => $achievement3->DateModified->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->ID => [
                        'ID' => $achievement2->ID,
                        'Title' => $achievement2->Title,
                        'Description' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->developer->User,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
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
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'NumAwardedToUser' => 1,
                'NumAwardedToUserHardcore' => 1,
                'UserCompletion' => '33.33%',
                'UserCompletionHardcore' => '33.33%',
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->developer->User,
                        'DateCreated' => $achievement1->DateCreated->__toString(),
                        'DateModified' => $achievement1->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                    $achievement3->ID => [
                        'ID' => $achievement3->ID,
                        'Title' => $achievement3->Title,
                        'Description' => $achievement3->Description,
                        'Points' => $achievement3->Points,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->developer->User,
                        'DateCreated' => $achievement3->DateCreated->__toString(),
                        'DateModified' => $achievement3->DateModified->__toString(),
                        'NumAwarded' => 2,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->ID => [
                        'ID' => $achievement2->ID,
                        'Title' => $achievement2->Title,
                        'Description' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->developer->User,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
                'HighestAwardKind' => null,
                'HighestAwardDate' => null,
            ]);
    }
}
