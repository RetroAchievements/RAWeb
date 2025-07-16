<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerProgressReset;
use App\Models\PlayerSession;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\PlayerProgressResetType;
use App\Platform\Services\PlayerGameActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class PlayerGameActivityServiceTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testActivity(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = $this->seedGame(achievements: 5, withHash: false);

        // ==== Start session at time1 ====
        $now = Carbon::now()->startOfSecond();
        $time1 = $now->clone()->subMinutes(100);
        Carbon::setTestNow($time1);
        /** @var PlayerSession $playerSession */
        $playerSession = (new ResumePlayerSessionAction())->execute($user, $game);
        $playerSession->duration = (int) $now->diffInMinutes($time1, true);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($playerSession->id, $session['playerSession']->id);
        $this->assertEquals($time1->timestamp, $session['startTime']->timestamp);
        $this->assertEquals($now->timestamp, $session['endTime']->timestamp);
        $this->assertEquals($now->setTimezone('UTC')->toDateTimeString(), $session['endTime']->setTimezone('UTC')->toDateTimeString());
        $this->assertEquals($playerSession->duration * 60, $session['duration']);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession->rich_presence, $time1);

        // ==== Unlock one achievement at time2 ====
        $time2 = $time1->clone()->addMinutes(5);
        $ach1 = $game->achievements()->first();
        $this->addHardcoreUnlock($user, $ach1, $time2);
        // UnlockPlayerAchievementAction updates duration to be 'unlock time - created_at'
        $playerSession->refresh();
        $playerSession->duration = (int) $now->diffInMinutes($time1, true);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time1->timestamp, $session['startTime']->timestamp);
        $this->assertEquals($playerSession->duration * 60, $session['duration']);
        $this->assertEquals($now->timestamp, $session['endTime']->timestamp);
        $this->assertEquals($now->setTimezone('UTC')->toDateTimeString(), $session['endTime']->setTimezone('UTC')->toDateTimeString());
        $this->assertEquals(2, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertRichPresenceEvent($session['events'][1], $playerSession->rich_presence, $time2);

        // ==== Unlock two achievements at time3 ====
        $time3 = $time2->clone()->addMinutes(6);
        $ach2 = $game->achievements()->skip(1)->first();
        $this->addHardcoreUnlock($user, $ach2, $time3);
        $ach3 = $game->achievements()->skip(2)->first();
        $this->addHardcoreUnlock($user, $ach3, $time3);
        // UnlockPlayerAchievementAction updates duration to be 'unlock time - created_at'
        $playerSession->refresh();
        $playerSession->duration = (int) $now->diffInMinutes($time1, true);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time1->timestamp, $session['startTime']->timestamp);
        $this->assertEquals($playerSession->duration * 60, $session['duration']);
        $this->assertEquals($now->timestamp, $session['endTime']->timestamp);
        $this->assertEquals($now->setTimezone('UTC')->toDateTimeString(), $session['endTime']->setTimezone('UTC')->toDateTimeString());
        $this->assertEquals(4, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $this->assertRichPresenceEvent($session['events'][3], $playerSession->rich_presence, $time3);

        // ==== Ping updates rich presence ====
        $time4 = $time3->clone()->addMinutes(8);
        $playerSession->refresh();
        $playerSession->rich_presence = "Updated Rich Presence";
        $playerSession->rich_presence_updated_at = $time4;
        $playerSession->duration = (int) $now->diffInMinutes($time1, true);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time1->timestamp, $session['startTime']->timestamp);
        $this->assertEquals($playerSession->duration * 60, $session['duration']);
        $this->assertEquals($now->timestamp, $session['endTime']->timestamp);
        $this->assertEquals($now->setTimezone('UTC')->toDateTimeString(), $session['endTime']->setTimezone('UTC')->toDateTimeString());
        $this->assertEquals(4, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $this->assertRichPresenceEvent($session['events'][3], $playerSession->rich_presence, $time4);

        // ==== New session ====
        $time5 = $time4->clone()->addHours(8);
        Carbon::setTestNow($time5);
        /** @var PlayerSession $playerSession2 */
        $playerSession2 = (new ResumePlayerSessionAction())->execute($user, $game);

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(2, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time1->timestamp, $session['startTime']->timestamp);
        $this->assertEquals($playerSession->duration * 60, $session['duration']);
        $this->assertEquals($now->timestamp, $session['endTime']->timestamp);
        $this->assertEquals($now->setTimezone('UTC')->toDateTimeString(), $session['endTime']->setTimezone('UTC')->toDateTimeString());
        $this->assertEquals(4, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $this->assertRichPresenceEvent($session['events'][3], $playerSession->rich_presence, $time4);
        $session = $activity->sessions[1];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time5->timestamp, $session['startTime']->timestamp);
        $this->assertEquals(60, $session['duration']); // new session always has 1 minute duration
        $this->assertEquals($time5->timestamp, $session['endTime']->timestamp); // actual duration captured by last_played_at - start time
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

        // ==== First session no longer exists (before sessions were tracked, or expired) ====
        $playerSession->delete();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(2, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Reconstructed, $session['type']);
        $this->assertEquals($time2->timestamp, $session['startTime']->timestamp);
        $this->assertEquals((int) $time3->diffInSeconds($time2, true), $session['duration']);
        $this->assertEquals($time3->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(3, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $session = $activity->sessions[1];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time5->timestamp, $session['startTime']->timestamp);
        $this->assertEquals(60, $session['duration']); // new session always has 1 minute duration
        $this->assertEquals($time5->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

        // ==== Manual unlock gets its own session ====
        $time6 = $time4->clone()->addHours(2);
        $ach4 = $game->achievements()->skip(3)->first();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        (new UnlockPlayerAchievementAction())->execute($user, $ach4, true, $time6, unlockedBy: $user2);

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(3, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Reconstructed, $session['type']);
        $this->assertEquals($time2->timestamp, $session['startTime']->timestamp);
        $this->assertEquals((int) $time3->diffInSeconds($time2, true), $session['duration']);
        $this->assertEquals($time3->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(3, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $session = $activity->sessions[1];
        $this->assertEquals(PlayerGameActivitySessionType::ManualUnlock, $session['type']);
        $this->assertEquals($time6->timestamp, $session['startTime']->timestamp);
        $this->assertEquals(0, $session['duration']);
        $this->assertEquals($time6->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(1, count($session['events']));
        $this->assertManualUnlockEvent($session['events'][0], $ach4->id, $time6, true, $user2);
        $session = $activity->sessions[2];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time5->timestamp, $session['startTime']->timestamp);
        $this->assertEquals(60, $session['duration']); // new session always has 1 minute duration
        $this->assertEquals($time5->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

        // ==== Generate summary information ====
        $summary = $activity->summarize();
        // first session is generated, so the achievement time will be the distance between achievements
        // second session has no unlocks, so will only be included in total time calculations.
        $firstSessionDuration = (int) $time3->diffInSeconds($time2, true);
        $adjustment = $firstSessionDuration / 3;
        $this->assertEquals($firstSessionDuration + $adjustment, $summary['achievementPlaytime']);
        $this->assertEquals(1, $summary['achievementSessionCount']);
        $this->assertEquals($adjustment, $summary['generatedSessionAdjustment']);
        $this->assertEquals($firstSessionDuration, $summary['totalUnlockTime']);
        $this->assertEquals($firstSessionDuration + $adjustment + 60, $summary['totalPlaytime']);

        // ==== Add unlock to second session ====
        $time7 = $time5->clone()->addMinutes(9);
        $ach5 = $game->achievements()->skip(4)->first();
        $this->addHardcoreUnlock($user, $ach5, $time7);
        // UnlockPlayerAchievementAction updates duration to be 'unlock time - created_at'
        $playerSession2->refresh();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(3, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals(PlayerGameActivitySessionType::Reconstructed, $session['type']);
        $this->assertEquals($time2->timestamp, $session['startTime']->timestamp);
        $this->assertEquals((int) $time3->diffInSeconds($time2, true), $session['duration']);
        $this->assertEquals($time3->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(3, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $session = $activity->sessions[1];
        $this->assertEquals(PlayerGameActivitySessionType::ManualUnlock, $session['type']);
        $this->assertEquals($time6->timestamp, $session['startTime']->timestamp);
        $this->assertEquals(0, $session['duration']);
        $this->assertEquals($time6->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(1, count($session['events']));
        $this->assertManualUnlockEvent($session['events'][0], $ach4->id, $time6, true, $user2);
        $session = $activity->sessions[2];
        $this->assertEquals(PlayerGameActivitySessionType::Player, $session['type']);
        $this->assertEquals($time5->timestamp, $session['startTime']->timestamp);
        $this->assertEquals((int) $time7->diffInSeconds($time5, true), $session['duration']); // new session always has 1 minute duration
        $this->assertEquals($time7->timestamp, $session['endTime']->timestamp);
        $this->assertEquals(2, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach5->id, $time7, true);
        $this->assertRichPresenceEvent($session['events'][1], $playerSession2->rich_presence, $time7);

        // ==== Generate summary information ====
        $summary = $activity->summarize();
        // first session is generated, so the achievement time will be the distance between achievements
        // second session has one unlocks, so will be included in both calculations.
        $firstSessionDuration = (int) $time3->diffInSeconds($time2, true);
        $secondSessionDuration = (int) $time7->diffInSeconds($time5, true);
        $totalDuration = $firstSessionDuration + $secondSessionDuration;
        $adjustment = $totalDuration / 4;
        $this->assertEquals($totalDuration + $adjustment, $summary['achievementPlaytime']);
        $this->assertEquals(2, $summary['achievementSessionCount']);
        $this->assertEquals($adjustment, $summary['generatedSessionAdjustment']);
        $this->assertEquals((int) $time7->diffInSeconds($time2, true), $summary['totalUnlockTime']);
        $this->assertEquals($firstSessionDuration + $adjustment + $secondSessionDuration, $summary['totalPlaytime']);
    }

    private function assertRichPresenceEvent(array $event, string $message, Carbon $time): void
    {
        $this->assertEquals(PlayerGameActivityEventType::RichPresence, $event['type']);
        $this->assertEquals($message, $event['description']);
        $this->assertEquals($time->timestamp, $event['when']->timestamp);
    }

    private function assertUnlockEvent(array $event, int $achievementId, Carbon $time, bool $hardcore): void
    {
        $this->assertEquals(PlayerGameActivityEventType::Unlock, $event['type']);
        $this->assertEquals($achievementId, $event['id']);
        $this->assertEquals($hardcore, $event['hardcore']);
        $this->assertEquals($time->timestamp, $event['when']->timestamp);
    }

    private function assertManualUnlockEvent(array $event, int $achievementId, Carbon $time, bool $hardcore, User $unlocker): void
    {
        $this->assertUnlockEvent($event, $achievementId, $time, $hardcore);
        $this->assertEquals($unlocker->id, $event['unlocker']->id);
    }

    public function testItShowsSessionsBeforeGameResetWithoutAchievements(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        $now = Carbon::now()->startOfSecond();

        // ... create the first session (before reset) - 100 minutes ago ...
        $timeBeforeReset = $now->clone()->subMinutes(100);
        Carbon::setTestNow($timeBeforeReset);

        /** @var PlayerSession $sessionBeforeReset */
        $sessionBeforeReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionBeforeReset->duration = 30;
        $sessionBeforeReset->save();

        // ... create the reset record - 50 minutes ago ...
        $resetTime = $now->clone()->subMinutes(50);
        Carbon::setTestNow($resetTime);
        PlayerProgressReset::create([
            'user_id' => $user->id,
            'type' => PlayerProgressResetType::Game,
            'type_id' => $game->id,
        ]);

        // ... create second session (after reset) - 20 minutes ago ...
        $timeAfterReset = $now->clone()->subMinutes(20);
        Carbon::setTestNow($timeAfterReset);

        /** @var PlayerSession $sessionAfterReset */
        $sessionAfterReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionAfterReset->duration = 15;
        $sessionAfterReset->save();

        // Act
        Carbon::setTestNow($now);
        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $summary = $activity->summarize();

        // Assert
        // ... should include both sessions (empty sessions before the reset are still shown) ...
        $this->assertEquals(2, count($activity->sessions));
        $this->assertEquals($sessionBeforeReset->id, $activity->sessions[0]['playerSession']->id);
        $this->assertEquals($sessionAfterReset->id, $activity->sessions[1]['playerSession']->id);
        $this->assertEquals(45 * 60, $summary['totalPlaytime']); // !! 30 + 15 minutes in seconds
    }

    public function testItShowsSessionsBeforeAccountResetWithoutAchievements(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        $now = Carbon::now()->startOfSecond();

        // ... create the first session (before reset) - 100 minutes ago ...
        $timeBeforeReset = $now->clone()->subMinutes(100);
        Carbon::setTestNow($timeBeforeReset);

        /** @var PlayerSession $sessionBeforeReset */
        $sessionBeforeReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionBeforeReset->duration = 30;
        $sessionBeforeReset->save();

        // ... create the account reset record - 50 minutes ago ...
        $resetTime = $now->clone()->subMinutes(50);
        Carbon::setTestNow($resetTime);
        PlayerProgressReset::create([
            'user_id' => $user->id,
            'type' => PlayerProgressResetType::Account,
            'type_id' => null,
        ]);

        // ... create second session (after reset) - 20 minutes ago ...
        $timeAfterReset = $now->clone()->subMinutes(20);
        Carbon::setTestNow($timeAfterReset);

        /** @var PlayerSession $sessionAfterReset */
        $sessionAfterReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionAfterReset->duration = 15;
        $sessionAfterReset->save();

        // Act
        Carbon::setTestNow($now);
        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $summary = $activity->summarize();

        // Assert
        // ... should include both sessions (empty sessions before the reset are still shown) ...
        $this->assertEquals(2, count($activity->sessions));
        $this->assertEquals($sessionBeforeReset->id, $activity->sessions[0]['playerSession']->id);
        $this->assertEquals($sessionAfterReset->id, $activity->sessions[1]['playerSession']->id);
        $this->assertEquals(45 * 60, $summary['totalPlaytime']); // !! 30 + 15 minutes in seconds
    }

    public function testItShowsSessionsBeforeSubsetGameResetWithoutAchievements(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        // ... create a main game and subset game ...
        /** @var Game $mainGame */
        $mainGame = $this->seedGame(withHash: false);
        /** @var Game $subsetGame */
        $subsetGame = $this->seedGame(withHash: false);

        // ... create achievement sets and link them ...
        $mainSet = AchievementSet::factory()->create();
        $subsetSet = AchievementSet::factory()->create();

        GameAchievementSet::create([
            'game_id' => $mainGame->id,
            'achievement_set_id' => $mainSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::create([
            'game_id' => $subsetGame->id,
            'achievement_set_id' => $subsetSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::create([
            'game_id' => $mainGame->id,
            'achievement_set_id' => $subsetSet->id, // !! linking subset to root game
            'type' => AchievementSetType::Bonus,
        ]);

        $now = Carbon::now()->startOfSecond();

        // ... create sessions on both games before reset ...
        $timeBeforeReset = $now->clone()->subMinutes(100);
        Carbon::setTestNow($timeBeforeReset);

        /** @var PlayerSession $mainSessionBeforeReset */
        $mainSessionBeforeReset = (new ResumePlayerSessionAction())->execute($user, $mainGame);
        $mainSessionBeforeReset->duration = 20;
        $mainSessionBeforeReset->save();

        /** @var PlayerSession $subsetSessionBeforeReset */
        $subsetSessionBeforeReset = (new ResumePlayerSessionAction())->execute($user, $subsetGame);
        $subsetSessionBeforeReset->duration = 10;
        $subsetSessionBeforeReset->save();

        // ... reset the subset game - 50 minutes ago ...
        $resetTime = $now->clone()->subMinutes(50);
        Carbon::setTestNow($resetTime);
        PlayerProgressReset::create([
            'user_id' => $user->id,
            'type' => PlayerProgressResetType::Game,
            'type_id' => $subsetGame->id,
        ]);

        // ... create sessions after reset ...
        $timeAfterReset = $now->clone()->subMinutes(20);
        Carbon::setTestNow($timeAfterReset);

        /** @var PlayerSession $mainSessionAfterReset */
        $mainSessionAfterReset = (new ResumePlayerSessionAction())->execute($user, $mainGame);
        $mainSessionAfterReset->duration = 15;
        $mainSessionAfterReset->save();

        /** @var PlayerSession $subsetSessionAfterReset */
        $subsetSessionAfterReset = (new ResumePlayerSessionAction())->execute($user, $subsetGame);
        $subsetSessionAfterReset->duration = 5;
        $subsetSessionAfterReset->save();

        // Act
        Carbon::setTestNow($now);
        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $mainGame, withSubsets: true);
        $summary = $activity->summarize();

        // Assert
        // ... should include all sessions (empty sessions before the reset are still shown) ...
        $this->assertEquals(4, count($activity->sessions));

        // ... verify we have all sessions ...
        $sessionIds = collect($activity->sessions)->pluck('playerSession.id')->toArray();
        $this->assertContains($mainSessionAfterReset->id, $sessionIds);
        $this->assertContains($subsetSessionAfterReset->id, $sessionIds);
        $this->assertContains($mainSessionBeforeReset->id, $sessionIds);
        $this->assertContains($subsetSessionBeforeReset->id, $sessionIds);

        // ... total time should be 20 + 10 + 15 + 5 = 50 minutes ...
        $this->assertEquals(50 * 60, $summary['totalPlaytime']); // !! 50 minutes in seconds
    }

    public function testItFiltersAchievementsButNotSessionsAfterReset(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = $this->seedGame(achievements: 5, withHash: false);

        $now = Carbon::now()->startOfSecond();

        // ... create a session with achievements before reset - 100 minutes ago ...
        $timeBeforeReset = $now->clone()->subMinutes(100);
        Carbon::setTestNow($timeBeforeReset);

        /** @var PlayerSession $sessionBeforeReset */
        $sessionBeforeReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionBeforeReset->duration = 30;
        $sessionBeforeReset->save();

        // ... unlock 2 achievements before reset ...
        $ach1 = $game->achievements()->first();
        $ach2 = $game->achievements()->skip(1)->first();
        $this->addHardcoreUnlock($user, $ach1, $timeBeforeReset->clone()->addMinutes(5));
        $this->addHardcoreUnlock($user, $ach2, $timeBeforeReset->clone()->addMinutes(10));

        // ... update session duration to match the last achievement time ...
        $sessionBeforeReset->refresh();
        $sessionBeforeReset->duration = 10; // 10 minutes to last achievement
        $sessionBeforeReset->save();

        // ... create the reset record - 50 minutes ago ...
        $resetTime = $now->clone()->subMinutes(50);
        Carbon::setTestNow($resetTime);
        PlayerProgressReset::create([
            'user_id' => $user->id,
            'type' => PlayerProgressResetType::Game,
            'type_id' => $game->id,
        ]);

        // ... create session with achievements after reset - 20 minutes ago ...
        $timeAfterReset = $now->clone()->subMinutes(20);
        Carbon::setTestNow($timeAfterReset);

        /** @var PlayerSession $sessionAfterReset */
        $sessionAfterReset = (new ResumePlayerSessionAction())->execute($user, $game);
        $sessionAfterReset->duration = 15;
        $sessionAfterReset->save();

        // ... unlock 1 achievement after reset ...
        $ach3 = $game->achievements()->skip(2)->first();
        $this->addHardcoreUnlock($user, $ach3, $timeAfterReset->clone()->addMinutes(5));

        // ... update session duration to match the achievement time ...
        $sessionAfterReset->refresh();
        $sessionAfterReset->duration = 5; // 5 minutes to achievement
        $sessionAfterReset->save();

        // Act
        Carbon::setTestNow($now);
        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);

        // Assert
        // ... should have both sessions ...
        $this->assertEquals(2, count($activity->sessions)); // !! both sessions are shown

        // ... first session should exist but have no unlock events (only rich presence) ...
        $firstSession = $activity->sessions[0];
        $this->assertEquals($sessionBeforeReset->id, $firstSession['playerSession']->id);
        $this->assertEquals(1, count($firstSession['events'])); // !! only rich presence event
        $this->assertRichPresenceEvent($firstSession['events'][0], $sessionBeforeReset->rich_presence, $timeBeforeReset->clone()->addMinutes(10));

        // ... second session should have the achievement unlocked after reset ...
        $secondSession = $activity->sessions[1];
        $this->assertEquals($sessionAfterReset->id, $secondSession['playerSession']->id);
        $this->assertEquals(2, count($secondSession['events'])); // !! unlock + rich presence
        $this->assertUnlockEvent($secondSession['events'][0], $ach3->id, $timeAfterReset->clone()->addMinutes(5), true);
        $this->assertRichPresenceEvent($secondSession['events'][1], $sessionAfterReset->rich_presence, $timeAfterReset->clone()->addMinutes(5));

        // ... achievements unlocked count should only include post-reset achievements ...
        $this->assertEquals(1, $activity->achievementsUnlocked); // !! only 1 achievement counted (after reset)
    }
}
