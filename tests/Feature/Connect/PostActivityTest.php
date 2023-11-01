<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ActivityType;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class PostActivityTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testPostActivity(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        // this is the legacy start_session API call
        $this->get($this->apiUrl('postactivity', ['a' => ActivityType::StartedPlaying, 'm' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->ID,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals("Playing " . $game->Title, $user1->RichPresenceMsg);

        // disallow anything other than StartedPlaying messages
        $this->get($this->apiUrl('postactivity', ['a' => ActivityType::StartedPlaying + 1, 'm' => $game->ID]))
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);
    }
}
