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
        $game1 = Game::factory()->create(['system_id' => $system1->id]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system2->id]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game1->id, 'image_name' => '12345']);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'image_name' => '23456']);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game2->id, 'image_name' => '34567']);

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
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username, 'm' => 0]))
            ->assertSuccessful()
            ->assertJsonCount(0)
            ->assertJson([]);

        // nothing in the last 1 minute
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username, 'm' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(0)
            ->assertJson([]);

        // one in the last 5 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username, 'm' => 5]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->id,
                    'Author' => $achievement3->developer->username,
                    'BadgeName' => $achievement3->image_name,
                    'BadgeURL' => '/Badge/' . $achievement3->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Title' => $achievement3->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
            ]);

        // two in the last 30 minutes (newest first)
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username, 'm' => 30]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->id,
                    'Author' => $achievement3->developer->username,
                    'BadgeName' => $achievement3->image_name,
                    'BadgeURL' => '/Badge/' . $achievement3->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
                [
                    'AchievementID' => $achievement2->id,
                    'Author' => $achievement2->developer->username,
                    'BadgeName' => $achievement2->image_name,
                    'BadgeURL' => '/Badge/' . $achievement2->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
            ]);

        // two in the last 60 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->id,
                    'Author' => $achievement3->developer->username,
                    'BadgeName' => $achievement3->image_name,
                    'BadgeURL' => '/Badge/' . $achievement3->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
                [
                    'AchievementID' => $achievement2->id,
                    'Author' => $achievement2->developer->username,
                    'BadgeName' => $achievement2->image_name,
                    'BadgeURL' => '/Badge/' . $achievement2->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
            ]);

        // three in the last 90 minutes
        $this->get($this->apiUrl('GetUserRecentAchievements', ['u' => $this->user->username, 'm' => 90]))
            ->assertSuccessful()
            ->assertJsonCount(3)
            ->assertJson([
                [
                    'AchievementID' => $achievement3->id,
                    'Author' => $achievement3->developer->username,
                    'BadgeName' => $achievement3->image_name,
                    'BadgeURL' => '/Badge/' . $achievement3->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock3Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement3->description,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Points' => $achievement3->points,
                    'TrueRatio' => $achievement3->points_weighted,
                    'Type' => $achievement3->type,
                    'Title' => $achievement3->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
                [
                    'AchievementID' => $achievement2->id,
                    'Author' => $achievement2->developer->username,
                    'BadgeName' => $achievement2->image_name,
                    'BadgeURL' => '/Badge/' . $achievement2->image_name . '.png',
                    'ConsoleName' => $system2->name,
                    'Date' => $unlock2Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement2->description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement2->points,
                    'TrueRatio' => $achievement2->points_weighted,
                    'Type' => $achievement2->type,
                    'Title' => $achievement2->title,
                    'GameID' => $game2->id,
                    'GameTitle' => $game2->title,
                    'GameURL' => '/game/' . $game2->id,
                ],
                [
                    'AchievementID' => $achievement1->id,
                    'Author' => $achievement1->developer->username,
                    'BadgeName' => $achievement1->image_name,
                    'BadgeURL' => '/Badge/' . $achievement1->image_name . '.png',
                    'ConsoleName' => $system1->name,
                    'Date' => $unlock1Date->format('Y-m-d H:i:s'),
                    'Description' => $achievement1->description,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Points' => $achievement1->points,
                    'TrueRatio' => $achievement1->points_weighted,
                    'Type' => $achievement1->type,
                    'Title' => $achievement1->title,
                    'GameID' => $game1->id,
                    'GameTitle' => $game1->title,
                    'GameURL' => '/game/' . $game1->id,
                ],
            ]);
    }
}
