<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UnlockPlayerAchievementActionTests extends TestCase
{
    use RefreshDatabase;

    public function testManualUnlockDoesntUpdateLastLogin(): void
    {
        $action = new UnlockPlayerAchievementAction();

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $lastLogin = $now->clone()->subDays(7);
        $user1->LastLogin = $lastLogin;
        $user1->save();
        $this->assertEquals($lastLogin, $user1->LastLogin);

        $system = $this->seedSystem();
        $game = $this->seedGame($system);
        $achievements = $this->seedAchievements(2, $game);
        $achievement1 = $achievements->first();
        $achievement2 = $achievements->slice(1, 1)->first();

        // if we don't create a player_game record before calling unlock, it will do so,
        // which updates the LastLogin.
        $user1->games()->attach($game);

        // manual unlock (should not create a player session or update LastLogin)
        $action->execute($user1, $achievement1, true, unlockedBy: $user2);

        $playerAchievement = $user1->playerAchievements()->firstWhere('achievement_id', $achievement1->id);
        $this->assertNotNull($playerAchievement);
        $this->assertEquals($user2->id, $playerAchievement->unlocker_id);

        $user1->refresh();
        $this->assertEquals($lastLogin, $user1->LastLogin);
        $this->assertEquals(0, $user1->playerSessions()->count());

        // normal unlock (LastLogin is actually updated twice by WriteUserActivity -
        // once for the PlayerSessionStarted/PlayerSessionResumed event and once for
        // the PlayerAchievementUnlocked event)
        $action->execute($user1, $achievement2, true);

        $playerAchievement = $user1->playerAchievements()->firstWhere('achievement_id', $achievement2->id);
        $this->assertNotNull($playerAchievement);
        $this->assertNull($playerAchievement->unlocker_id);

        $user1->refresh();
        $this->assertEquals($now, $user1->LastLogin);
        $this->assertEquals(1, $user1->playerSessions()->count());
    }
}
