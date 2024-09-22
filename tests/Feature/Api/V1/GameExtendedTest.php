<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class GameExtendedTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testGetGameExtendedUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameExtended', ['i' => 999999]))
            ->assertSuccessful()
            ->assertExactJson([]);
    }

    public function testGetGame(): void
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
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->create(['GameID' => $game->ID]); // unofficial

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

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->ID]))
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

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->ID, 'f' => AchievementFlag::Unofficial]))
            ->assertSuccessful()
            ->assertJson([
                'Achievements' => [
                    $achievement4->ID => [
                        'ID' => $achievement4->ID,
                        'Title' => $achievement4->Title,
                        'Description' => $achievement4->Description,
                        'Points' => $achievement4->Points,
                        'BadgeName' => $achievement4->BadgeName,
                        'DisplayOrder' => $achievement4->DisplayOrder,
                        'Author' => $achievement4->developer->User,
                        'DateCreated' => $achievement4->DateCreated->__toString(),
                        'DateModified' => $achievement4->DateModified->__toString(),
                        'NumAwarded' => 0,
                        'NumAwardedHardcore' => 0,
                    ],
                ],
            ]);
    }

    public function testGetGameClaimed(): void
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

        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Developer]);
        insertClaim(
            $user2,
            $game->id,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None
        );
        $claim = AchievementSetClaim::first();

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->ID]))
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
                'Achievements' => [],
                'Claims' => [
                    [
                        'User' => $claim->user->User,
                        'SetType' => $claim->SetType,
                        'ClaimType' => $claim->ClaimType,
                        'Created' => $claim->Created->__toString(),
                        'Expiration' => $claim->Finished->__toString(),
                    ],
                ],
            ]);
    }
}
