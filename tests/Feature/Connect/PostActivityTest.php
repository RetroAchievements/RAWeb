<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivityLegacy;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\TestsPlayerAchievements;
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

        /** @var UserActivityLegacy $activity */
        $activity = UserActivityLegacy::latest()->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ActivityType::StartedPlaying, $activity->activitytype);
        $this->assertEquals($game->ID, $activity->data);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals("Playing " . $game->Title, $user1->RichPresenceMsg);
    }
}
