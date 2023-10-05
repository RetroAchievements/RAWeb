<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UnlocksTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testUnlocks(): void
    {
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID]);

        $now = Carbon::now()->subSeconds(15); // 15-second offset so times aren't on the boundaries being queried
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $unlock2Date = $now->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);
        $unlock3Date = $now->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        // all unlocks for the game
        $this->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [$achievement1->ID, $achievement2->ID, $achievement3->ID],
            ]);

        // hardcore unlocks for the game
        $this->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [$achievement1->ID, $achievement2->ID],
            ]);

        // hardcore filter not specified, return all unlocks for the game
        $this->get($this->apiUrl('unlocks', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [$achievement1->ID, $achievement2->ID, $achievement3->ID],
            ]);

        // unknown game ID
        $this->get($this->apiUrl('unlocks', ['g' => 9999]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => 9999,
                'HardcoreMode' => false,
                'UserUnlocks' => [],
            ]);

        // via POST
        $this->post('dorequest.php', $this->apiParams('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [$achievement1->ID, $achievement2->ID],
            ]);
    }
}
