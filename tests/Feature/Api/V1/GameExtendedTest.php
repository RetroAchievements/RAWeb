<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use Tests\Feature\Platform\TestsPlayerAchievements;
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
                'Released' => $game->Released,
                'IsFinal' => 0,
                'NumAchievements' => 3,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 3, // user2 has no hardcore unlocks
                'Achievements' => [
                    $achievement1->ID => [
                        'ID' => $achievement1->ID,
                        'Title' => $achievement1->Title,
                        'Description' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'TrueRatio' => $achievement1->TrueRatio,
                        'BadgeName' => $achievement1->BadgeName,
                        'DisplayOrder' => $achievement1->DisplayOrder,
                        'Author' => $achievement1->Author,
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
                        'TrueRatio' => $achievement3->TrueRatio,
                        'BadgeName' => $achievement3->BadgeName,
                        'DisplayOrder' => $achievement3->DisplayOrder,
                        'Author' => $achievement3->Author,
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
                        'TrueRatio' => $achievement2->TrueRatio,
                        'BadgeName' => $achievement2->BadgeName,
                        'DisplayOrder' => $achievement2->DisplayOrder,
                        'Author' => $achievement2->Author,
                        'DateCreated' => $achievement2->DateCreated->__toString(),
                        'DateModified' => $achievement2->DateModified->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
            ]);
    }

    public function testGetGameClaimed(): void
    {
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
        ]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        insertClaim($user2->User, $game->ID, ClaimType::Primary, ClaimSetType::NewSet,
                    ClaimSpecial::None, Permissions::Developer);
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
                'Released' => $game->Released,
                'IsFinal' => 0,
                'Achievements' => [],
                'Claims' => [
                    [
                        'User' => $claim->User,
                        'SetType' => $claim->SetType,
                        'ClaimType' => $claim->ClaimType,
                        'Created' => $claim->Created->__toString(),
                        'Expiration' => $claim->Finished->__toString(),
                    ],
                ],
            ]);
    }
}
