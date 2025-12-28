<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Enums\AchievementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UnlockPlayerAchievementActionTest extends TestCase
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

    public function testEntireSetManuallyUnlockAwardsBadge(): void
    {
        $game = $this->seedGame(achievements: 6);

        $achievement1 = $game->achievements->get(0);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = $game->achievements->get(1);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = $game->achievements->get(2);
        $achievement3->type = AchievementType::Progression;
        $achievement3->save();
        $achievement4 = $game->achievements->get(3);
        $achievement4->type = AchievementType::WinCondition;
        $achievement4->save();
        $achievement5 = $game->achievements->get(4);
        $achievement6 = $game->achievements->get(5);

        $action = new UnlockPlayerAchievementAction();

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $action->execute($user1, $achievement1, true, unlockedBy: $user2);
        $action->execute($user1, $achievement2, true, unlockedBy: $user2);
        $action->execute($user1, $achievement3, true, unlockedBy: $user2);

        // manual unlocks don't create player sessions
        $user1->refresh();
        $this->assertEquals(0, $user1->playerSessions()->count());

        $playerGame = $user1->playerGames()->firstWhere('game_id', $game->id);
        $this->assertNotNull($playerGame);
        $this->assertEquals(3, $playerGame->achievements_unlocked);
        $this->assertEquals(3, $playerGame->achievements_unlocked_hardcore);

        // this should mark the game as beaten for the user.
        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);
        $action->execute($user1, $achievement4, true, unlockedBy: $user2);

        $playerGame->refresh();
        $this->assertEquals($now2, $playerGame->beaten_at);
        $this->assertEquals($now2, $playerGame->beaten_hardcore_at);

        // this should mark the game as completed for the user.
        $now3 = $now2->clone()->addMinutes(2);
        Carbon::setTestNow($now3);
        $action->execute($user1, $achievement5, true, unlockedBy: $user2);
        $action->execute($user1, $achievement6, true, unlockedBy: $user2);

        $playerGame->refresh();
        $this->assertEquals($now2, $playerGame->beaten_at);
        $this->assertEquals($now2, $playerGame->beaten_hardcore_at);
        $this->assertEquals($now3, $playerGame->completed_at);
        $this->assertEquals($now3, $playerGame->completed_hardcore_at);

        // there still shouldn't be any player sessions
        $user1->refresh();
        $this->assertEquals(0, $user1->playerSessions()->count());
    }

    public function testSubsetAchievementThroughCoreSetDoesntCreateSubsetSession(): void
    {
        $coreGame = $this->seedGame(achievements: 6);
        $coreGameHash = $coreGame->hashes()->first();
        $subsetGame = $this->seedGame(achievements: 2);
        $subsetAchievement = $subsetGame->achievements->get(0);

        $user = User::factory()->create();

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        // create a session for the core game
        $sessionLength = 10;
        (new ResumePlayerSessionAction())->execute($user, $coreGame, timestamp: $now->clone()->subMinutes($sessionLength), presence: 'Doing things');

        // unlock an achievement from the subset using the core game's hash
        $action = new UnlockPlayerAchievementAction();
        $action->execute($user, $subsetAchievement, true, gameHash: $coreGameHash);

        // core session should be extended
        $corePlayerSession = PlayerSession::where('user_id', $user->id)->where('game_id', $coreGame->id)->first();
        $this->assertNotNull($corePlayerSession);
        $this->assertEquals('Doing things', $corePlayerSession->rich_presence);
        $this->assertEquals($sessionLength, $corePlayerSession->duration);

        // subset session should not be created
        $subsetPlayerSession = PlayerSession::where('user_id', $user->id)->where('game_id', $subsetGame->id)->first();
        $this->assertNull($subsetPlayerSession);

        // subset player_games record should be created and have points from the unlock
        $subsetPlayerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $subsetGame->id)->first();
        $this->assertNotNull($subsetPlayerGame);
        $this->assertEquals($subsetAchievement->points, $subsetPlayerGame->points_hardcore);
    }
}
