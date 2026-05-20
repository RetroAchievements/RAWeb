<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UnlockPlayerAchievementActionTest extends TestCase
{
    use RefreshDatabase;

    public function testUnlockingFirstAchievementDispatchesPlayerGameAttachedWhenItCreatesPlayerGame(): void
    {
        $user = User::factory()->create();
        $game = $this->seedGame(achievements: 1);
        $achievement = $game->achievements->first();

        Event::fake([
            PlayerAchievementUnlocked::class,
            PlayerGameAttached::class,
        ]);

        (new UnlockPlayerAchievementAction())->execute($user, $achievement, true);

        Event::assertDispatched(PlayerAchievementUnlocked::class);
        Event::assertDispatched(function (PlayerGameAttached $event) use ($game, $user): bool {
            return $event->user->is($user) && $event->game->is($game);
        });
    }

    public function testCrossGameHashUnlockDispatchesPlayerGameAttachedForHashGame(): void
    {
        $user = User::factory()->create();
        $sessionGame = $this->seedGame(achievements: 1);
        $sessionGameHash = $sessionGame->hashes()->first();
        $achievementGame = $this->seedGame(achievements: 1);
        $achievement = $achievementGame->achievements->first();

        Event::fake([
            PlayerAchievementUnlocked::class,
            PlayerGameAttached::class,
        ]);

        (new UnlockPlayerAchievementAction())->execute($user, $achievement, true, gameHash: $sessionGameHash);

        Event::assertDispatched(PlayerAchievementUnlocked::class);
        Event::assertDispatched(function (PlayerGameAttached $event) use ($sessionGame, $user): bool {
            return $event->user->is($user) && $event->game->is($sessionGame);
        });

        $sessionPlayerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $sessionGame->id)->first();
        $this->assertNotNull($sessionPlayerGame);

        $achievementPlayerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $achievementGame->id)->first();
        $this->assertNotNull($achievementPlayerGame);
    }

    public function testManualUnlockDoesntUpdateLastActivityAt(): void
    {
        $action = new UnlockPlayerAchievementAction();

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $lastLogin = $now->clone()->subDays(7);
        $user1->last_activity_at = $lastLogin;
        $user1->save();
        $this->assertEquals($lastLogin, $user1->last_activity_at);

        $system = $this->seedSystem();
        $game = $this->seedGame($system);
        $achievements = $this->seedAchievements(2, $game);
        $achievement1 = $achievements->first();
        $achievement2 = $achievements->slice(1, 1)->first();

        // if we don't create a player_game record before calling unlock, it will do so,
        // which updates the last_activity_at.
        $user1->games()->attach($game);

        // manual unlock (should not create a player session or update last_activity_at)
        $action->execute($user1, $achievement1, true, unlockedBy: $user2);

        $playerAchievement = $user1->playerAchievements()->firstWhere('achievement_id', $achievement1->id);
        $this->assertNotNull($playerAchievement);
        $this->assertEquals($user2->id, $playerAchievement->unlocker_id);

        $user1->refresh();
        $this->assertEquals($lastLogin, $user1->last_activity_at);
        $this->assertEquals(0, $user1->playerSessions()->count());

        // normal unlock (last_activity_at is actually updated twice by WriteUserActivity -
        // once for the PlayerSessionStarted/PlayerSessionResumed event and once for
        // the PlayerAchievementUnlocked event)
        $action->execute($user1, $achievement2, true);

        $playerAchievement = $user1->playerAchievements()->firstWhere('achievement_id', $achievement2->id);
        $this->assertNotNull($playerAchievement);
        $this->assertNull($playerAchievement->unlocker_id);

        $user1->refresh();
        $this->assertEquals($now, $user1->last_activity_at);
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

    public function testFanOutSkipsSelfReferentialEventAchievement(): void
    {
        // a self-referential EventAchievement (source_achievement_id == achievement_id) would
        // dispatch a job re-targeting the same achievement and infinite loop, pinning our infra
        $user = User::factory()->create();
        $game = $this->seedGame(achievements: 1);
        /** @var Achievement $achievement */
        $achievement = $game->achievements->first();

        DB::table('event_achievements')->insert([
            'achievement_id' => $achievement->id,
            'source_achievement_id' => $achievement->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Queue::fake();

        (new UnlockPlayerAchievementAction())->execute($user, $achievement, true);

        Queue::assertNotPushed(
            UnlockPlayerAchievementJob::class,
            fn (UnlockPlayerAchievementJob $job) => in_array(Achievement::class . ':' . $achievement->id, $job->tags(), true)
        );
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
