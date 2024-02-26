<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Community\Enums\AwardType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSession;
use App\Platform\Actions\UnlockPlayerAchievement;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\PlayerGameActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
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
        $now = Carbon::now()->floorSecond();
        $time1 = $now->clone()->subMinutes(100);
        Carbon::setTestNow($time1);
        /** @var PlayerSession $playerSession */
        $playerSession = (new ResumePlayerSession)->execute($user, $game);
        $playerSession->duration = $now->diffInSeconds($time1);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($playerSession->id, $session['playerSession']->id);
        $this->assertEquals($time1, $session['startTime']);
        $this->assertEquals($now, $session['endTime']);
        $this->assertEquals($playerSession->duration, $session['duration']);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession->rich_presence, $time1);

        // ==== Unlock one achievement at time2 ====
        $time2 = $time1->clone()->addMinutes(5);
        $ach1 = $game->achievements()->first();
        $this->addHardcoreUnlock($user, $ach1, $time2);
        // UnlockPlayerAchievement action updates duration to be 'unlock time - created_at'
        $playerSession->refresh();
        $playerSession->duration = $now->diffInSeconds($time1);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time1, $session['startTime']);
        $this->assertEquals($playerSession->duration, $session['duration']);
        $this->assertEquals($now, $session['endTime']);
        $this->assertEquals(2, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertRichPresenceEvent($session['events'][1], $playerSession->rich_presence, $time2);

        // ==== Unlock two achievements at time3 ====
        $time3 = $time2->clone()->addMinutes(6);
        $ach2 = $game->achievements()->skip(1)->first();
        $this->addHardcoreUnlock($user, $ach2, $time3);
        $ach3 = $game->achievements()->skip(2)->first();
        $this->addHardcoreUnlock($user, $ach3, $time3);
        // UnlockPlayerAchievement action updates duration to be 'unlock time - created_at'
        $playerSession->refresh();
        $playerSession->duration = $now->diffInSeconds($time1);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time1, $session['startTime']);
        $this->assertEquals($playerSession->duration, $session['duration']);
        $this->assertEquals($now, $session['endTime']);
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
        $playerSession->duration = $now->diffInSeconds($time1);
        $playerSession->save();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(1, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time1, $session['startTime']);
        $this->assertEquals($playerSession->duration, $session['duration']);
        $this->assertEquals($now, $session['endTime']);
        $this->assertEquals(4, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $this->assertRichPresenceEvent($session['events'][3], $playerSession->rich_presence, $time4);

        // ==== New session ====
        $time5 = $time4->clone()->addHours(8);
        Carbon::setTestNow($time5);
        /** @var PlayerSession $playerSession2 */
        $playerSession2 = (new ResumePlayerSession)->execute($user, $game);

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(2, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time1, $session['startTime']);
        $this->assertEquals($playerSession->duration, $session['duration']);
        $this->assertEquals($now, $session['endTime']);
        $this->assertEquals(4, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $this->assertRichPresenceEvent($session['events'][3], $playerSession->rich_presence, $time4);
        $session = $activity->sessions[1];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time5, $session['startTime']);
        $this->assertEquals(1, $session['duration']); // new session always has 1 second duration
        $this->assertEquals($time5->clone()->addSeconds(1), $session['endTime']);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

        // ==== First session no longer exists (before sessions were tracked, or expired) ====
        $playerSession->delete();

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(2, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('generated', $session['type']);
        $this->assertEquals($time2, $session['startTime']);
        $this->assertEquals($time3->diffInSeconds($time2), $session['duration']);
        $this->assertEquals($time3, $session['endTime']);
        $this->assertEquals(3, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $session = $activity->sessions[1];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time5, $session['startTime']);
        $this->assertEquals(1, $session['duration']); // new session always has 1 second duration
        $this->assertEquals($time5->clone()->addSeconds(1), $session['endTime']);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

        // ==== Manual unlock gets its own session ====
        $time6 = $time4->clone()->addHours(2);
        $ach4 = $game->achievements()->skip(3)->first();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        (new UnlockPlayerAchievement())->execute($user, $ach4, true, $time6, unlockedBy: $user2);

        $activity = new PlayerGameActivityService();
        $activity->initialize($user, $game);
        $this->assertEquals(3, count($activity->sessions));
        $session = $activity->sessions[0];
        $this->assertEquals('generated', $session['type']);
        $this->assertEquals($time2, $session['startTime']);
        $this->assertEquals($time3->diffInSeconds($time2), $session['duration']);
        $this->assertEquals($time3, $session['endTime']);
        $this->assertEquals(3, count($session['events']));
        $this->assertUnlockEvent($session['events'][0], $ach1->id, $time2, true);
        $this->assertUnlockEvent($session['events'][1], $ach2->id, $time3, true);
        $this->assertUnlockEvent($session['events'][2], $ach3->id, $time3, true);
        $session = $activity->sessions[1];
        $this->assertEquals('manual-unlock', $session['type']);
        $this->assertEquals($time6, $session['startTime']);
        $this->assertEquals(0, $session['duration']);
        $this->assertEquals($time6, $session['endTime']);
        $this->assertEquals(1, count($session['events']));
        $this->assertManualUnlockEvent($session['events'][0], $ach4->id, $time6, true, $user2);
        $session = $activity->sessions[2];
        $this->assertEquals('player-session', $session['type']);
        $this->assertEquals($time5, $session['startTime']);
        $this->assertEquals(1, $session['duration']); // new session always has 1 second duration
        $this->assertEquals($time5->clone()->addSeconds(1), $session['endTime']);
        $this->assertEquals(1, count($session['events']));
        $this->assertRichPresenceEvent($session['events'][0], $playerSession2->rich_presence, $time5);

    }

    private function assertRichPresenceEvent(array $event, string $message, Carbon $time): void
    {
        $this->assertEquals('rich-presence', $event['type']);
        $this->assertEquals($message, $event['description']);
        $this->assertEquals($time, $event['when']);
    }

    private function assertUnlockEvent(array $event, int $achievementId, Carbon $time, bool $hardcore): void
    {
        $this->assertEquals('unlock', $event['type']);
        $this->assertEquals($achievementId, $event['id']);
        $this->assertEquals($hardcore, $event['hardcore']);
        $this->assertEquals($time, $event['when']);
    }

    private function assertManualUnlockEvent(array $event, int $achievementId, Carbon $time, bool $hardcore, User $unlocker): void
    {
        $this->assertUnlockEvent($event, $achievementId, $time, $hardcore);
        $this->assertEquals($unlocker->id, $event['unlocker']->id);
    }
}
