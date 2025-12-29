<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\User;
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

        /** @var Game $game */
        $game = $this->seedGame();

        // this is the legacy start_session API call
        $this->get($this->apiUrl('postactivity', ['a' => 3, 'm' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);

        /** @var User $user1 */
        $user1 = User::whereName($this->user->User)->first();
        $this->assertEquals($game->id, $user1->LastGameID);
        $this->assertEquals("Playing " . $game->title, $user1->RichPresenceMsg);

        // disallow anything other than StartedPlaying messages
        $this->get($this->apiUrl('postactivity', ['a' => 4, 'm' => $game->id]))
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // unknown game
        $this->get($this->apiUrl('postactivity', ['a' => 3, 'm' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Unknown game.",
                "Code" => "not_found",
                "Status" => 404,
            ]);
    }
}
