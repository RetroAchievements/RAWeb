<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Community\Models\UserActivity;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\User;
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

        /** @var UserActivity $activity */
        $activity = UserActivity::latest()->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ActivityType::StartedPlaying, $activity->activitytype);
        $this->assertEquals($game->ID, $activity->data);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals("Playing " . $game->Title, $user1->RichPresenceMsg);
    }
}
