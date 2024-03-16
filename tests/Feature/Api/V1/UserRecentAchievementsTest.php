<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserRecentAchievementsTest extends TestCase
{
    use BootstrapsApiV1;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testGetUserRecentAchievements(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var Game $game1 */
        $game1 = Game::factory()->create(['ConsoleID' => $system1->ID]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system2->ID]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game1->ID, 'BadgeName' => '12345']);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'BadgeName' => '23456']);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->progression()->create(['GameID' => $game2->ID, 'BadgeName' => '34567']);

        $now = Carbon::now()->subSeconds(15); // 15-second offset so times aren't on the boundaries being queried
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $unlock2Date = $now->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);
        $unlock3Date = $now->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $achievement1->refresh();
        $achievement2->refresh();
        $achievement3->refresh();

        // nothing in the last 0 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User, 'm' => 0]))
            ->assertSuccessful()
            ->assertJsonCount(0)
            ->assertJson([]);

        // nothing in the last 1 minute
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User, 'm' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(0)
            ->assertJson([]);

        // one in the last 5 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User, 'm' => 5]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->ID,
                    'Author' => $achievement3->Author,
                    'BadgeName' => $achievement3->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement3->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->Description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->Points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Title' => $achievement3->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
            ]);

        // two in the last 30 minutes (newest first)
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User, 'm' => 30]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->ID,
                    'Author' => $achievement3->Author,
                    'BadgeName' => $achievement3->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement3->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->Description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->Points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
                [
                    'AchievementID' => $achievement2->ID,
                    'Author' => $achievement2->Author,
                    'BadgeName' => $achievement2->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement2->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->Description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->Points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
            ]);

        // two in the last 60 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->ID,
                    'Author' => $achievement3->Author,
                    'BadgeName' => $achievement3->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement3->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->Description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->Points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
                [
                    'AchievementID' => $achievement2->ID,
                    'Author' => $achievement2->Author,
                    'BadgeName' => $achievement2->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement2->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->Description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->Points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
            ]);

        // three in the last 90 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->User, 'm' => 90]))
            ->assertSuccessful()
            ->assertJsonCount(3)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->ID,
                    'Author' => $achievement3->Author,
                    'BadgeName' => $achievement3->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement3->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->Description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->Points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
                [
                    'AchievementID' => $achievement2->ID,
                    'Author' => $achievement2->Author,
                    'BadgeName' => $achievement2->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement2->BadgeName . '.png',
                    'ConsoleName' => $system2->Name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->Description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->Points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->Title,
                    'GameID' => $game2->ID,
                    'GameTitle' => $game2->Title,
                    'GameURL' => '/game/' . $game2->ID,
                ],
                [
                    'AchievementID' => $achievement1->ID,
                    'Author' => $achievement1->Author,
                    'BadgeName' => $achievement1->BadgeName,
                    'BadgeURL' => '/Badge/' . $achievement1->BadgeName . '.png',
                    'ConsoleName' => $system1->Name,
                    'Date' => $unlock1Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement1->Description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement1->Points,
                    'TrueRatio' => $achievement1->points_weighted,
                    'Type' => $achievement1->type,
                    'Title' => $achievement1->Title,
                    'GameID' => $game1->ID,
                    'GameTitle' => $game1->Title,
                    'GameURL' => '/game/' . $game1->ID,
                ],
            ]);
    }
}
