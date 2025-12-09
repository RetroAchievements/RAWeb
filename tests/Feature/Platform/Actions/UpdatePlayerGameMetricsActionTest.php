<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\PlayerAchievement;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsAuditComments;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerGameMetricsActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsAuditComments;
    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testMetrics(): void
    {
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $user = User::factory()->create();
        User::factory()->create(['User' => 'RACheats']);
        $this->addServerUser();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 3]);
        $achievements = $game->achievements()->published()->get();
        $achievementSet = $game->achievementSets()->where('type', AchievementSetType::Core)->first();

        $this->addSoftcoreUnlock($user, $achievements->get(0));

        $playerGame = PlayerGame::first();
        $createdAt = $playerGame->created_at;

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $game->refresh();
        $playerGame->refresh();

        $versionHash = $game->achievement_set_version_hash;

        $lastPlayedAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(0)->id)
            ->value('unlocked_at');

        $startedAt = $lastPlayedAt;
        $timeTaken = 0;
        $timeTakenHardcore = 0;

        $firstUnlockAt = $startedAt;
        $firstUnlockHardcoreAt = 0;
        $lastUnlockAt = $firstUnlockAt;
        $lastUnlockHardcoreAt = 0;

        $lastPlayedAt = $lastPlayedAt->clone()->addMinutes(1); // new session automatically has duration of 1

        $this->assertEquals(1, $playerGame->achievements_total);
        $this->assertEquals(1, $playerGame->achievements_unlocked);
        $this->assertEquals(0, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(1, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastUnlockAt, $playerGame->last_played_at);
        $this->assertEquals($lastUnlockAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals(3, $playerGame->points_total);
        $this->assertEquals(3, $playerGame->points);
        $this->assertEquals(0, $playerGame->points_hardcore);
        $this->assertEquals(0, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals($lastUnlockAt, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);

        $playerAchievementSet = PlayerAchievementSet::query()
            ->where('achievement_set_id', $achievementSet->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(1, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(0, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals($timeTaken, $playerAchievementSet->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(1, $playerAchievementSet->completion_percentage);
        $this->assertEquals(null, $playerAchievementSet->completion_percentage_hardcore);
        $this->assertEquals($lastUnlockAt, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);
        $this->assertEquals($lastUnlockAt, $playerAchievementSet->last_unlock_at);
        $this->assertEquals(null, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals(3, $playerAchievementSet->points);
        $this->assertEquals(0, $playerAchievementSet->points_hardcore);
        $this->assertEquals(0, $playerAchievementSet->points_weighted);

        Achievement::factory()->published()->count(3)->create(['GameID' => $game->id, 'Points' => 4]);
        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 5, 'type' => AchievementType::Progression]);
        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 10, 'type' => AchievementType::WinCondition]);
        $achievements = $game->achievements()->published()->get();

        $this->addSoftcoreUnlock($user, $achievements->get(1));
        $this->addSoftcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $achievements->get(4)); // Progression
        $this->addHardcoreUnlock($user, $achievements->get(3));

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $game->refresh();
        $playerGame->refresh();
        $playerAchievementSet->refresh();

        $versionHash = $game->achievement_set_version_hash;

        $lastPlayedAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(3)->id)
            ->value('unlocked_at');

        $timeTaken = $startedAt->diffInSeconds($lastPlayedAt);
        $timeTakenHardcore = $timeTaken;

        $firstUnlockHardcoreAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(4)->id)
            ->value('unlocked_at');

        $lastUnlockAt = $lastPlayedAt;
        $lastUnlockHardcoreAt = $lastPlayedAt;

        $lastPlayedAt = $lastPlayedAt->clone()->addMinutes(1); // new session automatically has duration of 1

        $this->assertEquals(6, $playerGame->achievements_total);
        $this->assertEquals(5, $playerGame->achievements_unlocked);
        $this->assertEquals(2, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(3, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastUnlockAt, $playerGame->last_played_at);
        $this->assertEquals($lastUnlockAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals(30, $playerGame->points_total);
        $this->assertEquals(20, $playerGame->points);
        $this->assertEquals(9, $playerGame->points_hardcore);
        $this->assertEquals(9, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals(null, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);

        $this->assertEquals(5, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(2, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals($timeTaken, $playerAchievementSet->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(83, round($playerAchievementSet->completion_percentage * 100));
        $this->assertEquals(33, round($playerAchievementSet->completion_percentage_hardcore * 100));
        $this->assertEquals(null, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);
        $this->assertEquals($lastUnlockAt, $playerAchievementSet->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals(20, $playerAchievementSet->points);
        $this->assertEquals(9, $playerAchievementSet->points_hardcore);
        $this->assertEquals(9, $playerAchievementSet->points_weighted);

        $this->addHardcoreUnlock($user, $achievements->get(5)); // Win Condition
        $this->addHardcoreUnlock($user, $achievements->get(1));

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $game->refresh();
        $playerGame->refresh();
        $playerAchievementSet->refresh();

        $versionHash = $game->achievement_set_version_hash;

        $lastPlayedAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(1)->id)
            ->value('unlocked_hardcore_at');

        $timeTaken = $startedAt->diffInSeconds($lastPlayedAt);
        $timeTakenHardcore = $timeTaken;

        $lastUnlockAt = $lastPlayedAt;
        $lastUnlockHardcoreAt = $lastPlayedAt;

        $lastPlayedAt = $lastPlayedAt->clone()->addMinutes(1); // new session automatically has duration of 1

        $beatenAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(5)->id)
            ->value('unlocked_at');

        $beatenHardcoreAt = $beatenAt;

        $this->assertEquals(6, $playerGame->achievements_total);
        $this->assertEquals(6, $playerGame->achievements_unlocked);
        $this->assertEquals(4, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(2, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastUnlockAt, $playerGame->last_played_at);
        $this->assertEquals($beatenAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals(30, $playerGame->points_total);
        $this->assertEquals(30, $playerGame->points);
        $this->assertEquals(23, $playerGame->points_hardcore);
        $this->assertEquals(23, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals($beatenAt, $playerGame->beaten_at);
        $this->assertEquals($beatenHardcoreAt, $playerGame->beaten_hardcore_at);
        $this->assertEquals($beatenAt, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);

        $this->assertEquals(6, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(4, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals($timeTaken, $playerAchievementSet->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(100, round($playerAchievementSet->completion_percentage * 100));
        $this->assertEquals(67, round($playerAchievementSet->completion_percentage_hardcore * 100));
        $this->assertEquals($lastUnlockAt, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);
        $this->assertEquals($lastUnlockAt, $playerAchievementSet->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals(30, $playerAchievementSet->points);
        $this->assertEquals(23, $playerAchievementSet->points_hardcore);
        $this->assertEquals(23, $playerAchievementSet->points_weighted);

        // no suspicious activity should have been reported.
        $this->assertEquals(0, MessageThread::count());
    }

    public function testSuspiciousBeatTimeGeneratesMail(): void
    {
        $now = Carbon::now()->subMinutes(15)->startOfSecond();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $raCheats = User::factory()->create(['User' => 'RACheats']);
        $this->addServerUser();

        $game = $this->seedGame(achievements: 6, withHash: false);
        $game->median_time_to_beat_hardcore = 23456;
        $game->times_beaten_hardcore = 26;
        $game->save();

        $achievement1 = $game->achievements->get(0);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = $game->achievements->get(1);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = $game->achievements->get(2);
        $achievement3->type = AchievementType::WinCondition;
        $achievement3->save();

        $this->addHardcoreUnlock($user, $achievement1);

        $now = $now->addMinutes(6);
        Carbon::setTestNow($now);
        $this->addHardcoreUnlock($user, $achievement2);

        $now = $now->addMinutes(8);
        Carbon::setTestNow($now);
        $this->addHardcoreUnlock($user, $achievement3);

        $playerGame = PlayerGame::first();
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $game->refresh();
        $playerGame->refresh();

        $this->assertNotNull($playerGame->beaten_hardcore_at);
        $this->assertEquals(14*60, $playerGame->time_to_beat_hardcore);

        $thread = MessageThread::first();
        $this->assertNotNull($thread);
        $this->assertEquals("Suspicious User: $user->display_name", $thread->title);

        $participant = MessageThreadParticipant::first();
        $this->assertNotNull($participant);
        $this->assertEquals($raCheats->ID, $participant->user_id);
        $this->assertEquals($thread->id, $participant->thread_id);

        $message = Message::first();
        $this->assertNotNull($message);
        $this->assertEquals($thread->id, $message->thread_id);
        $this->assertEquals($this->serverUser->ID, $message->author_id);
        $this->assertNull($message->sent_by_id);
        $this->assertEquals("{$user->display_name} beat {$game->Title} in hardcore in 14 minutes - much faster than the median beat time of 6 hours 30 minutes 56 seconds.", $message->body);
    }
}
