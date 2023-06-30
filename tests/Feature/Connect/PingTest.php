<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\TestsPlayerAchievements;
use Tests\TestCase;

class PingTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testPing(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        // this API requires POST
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Doing good']))
            ->assertExactJson([
                'Success' => true,
            ]);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);

        // string sent by GET will not update user's rich presence message
        $this->get($this->apiUrl('ping', ['g' => $game->ID, 'm' => 'Doing better']))
            ->assertExactJson([
                'Success' => true,
            ]);

        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);
    }
}
