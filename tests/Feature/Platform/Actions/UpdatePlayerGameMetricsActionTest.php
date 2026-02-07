<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
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
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerGameMetricsActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testMetrics(): void
    {
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->promoted()->count(1)->create(['game_id' => $game->id, 'points' => 3]);
        $achievements = $game->achievements()->promoted()->get();
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

        Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id, 'points' => 4]);
        Achievement::factory()->promoted()->count(1)->create(['game_id' => $game->id, 'points' => 5, 'type' => AchievementType::Progression]);
        Achievement::factory()->promoted()->count(1)->create(['game_id' => $game->id, 'points' => 10, 'type' => AchievementType::WinCondition]);
        $achievements = $game->achievements()->promoted()->get();

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
        $this->assertEquals(745, $playerGame->points_weighted);
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
        $this->assertEquals(745, $playerAchievementSet->points_weighted);

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
        $this->assertEquals(1905, $playerGame->points_weighted);
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
        $this->assertEquals(1905, $playerAchievementSet->points_weighted);
    }

    public function testSubsetMetrics(): void
    {
        $time1 = Carbon::now()->startOfSecond();
        Carbon::setTestNow($time1);

        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $subsetGame = $this->seedSubset($game);

        Achievement::factory()->promoted()->count(2)->create(['game_id' => $game->id, 'points' => 3]);
        $achievements = $game->achievements()->promoted()->get();
        $achievementSet = $game->achievementSets()->where('type', AchievementSetType::Core)->first();
        $achievementSet->achievements_first_published_at = $time1->clone()->subMinutes(10);
        $achievementSet->save();
        $achievements->get(0)->type = AchievementType::Progression;
        $achievements->get(0)->save();
        $achievements->get(1)->type = AchievementType::WinCondition;
        $achievements->get(1)->save();

        Achievement::factory()->promoted()->count(3)->create(['game_id' => $subsetGame->id, 'points' => 3]);
        $subsetAchievements = $subsetGame->achievements()->promoted()->get();
        $subsetAchievementSet = $subsetGame->achievementSets()->where('type', AchievementSetType::Core)->first();
        $subsetAchievementSet->achievements_first_published_at = $time1->clone()->subMinutes(7);
        $subsetAchievementSet->save();

        $this->addHardcoreUnlock($user, $achievements->get(0));

        $time2 = $time1->clone()->addMinutes(1);
        Carbon::setTestNow($time2);
        $this->addHardcoreUnlock($user, $subsetAchievements->get(0));

        $playerGame = PlayerGame::where('game_id', $game->id)->first();
        $subsetPlayerGame = PlayerGame::where('game_id', $subsetGame->id)->first();
        $playerAchievementSet = PlayerAchievementSet::where('achievement_set_id', $achievementSet->id)->first();
        $subsetPlayerAchievementSet = PlayerAchievementSet::where('achievement_set_id', $subsetAchievementSet->id)->first();

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $playerGame->refresh();
        $this->assertEquals(2, $playerGame->achievements_total);
        $this->assertEquals(1, $playerGame->achievements_unlocked);
        $this->assertEquals(1, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(0, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($time2, $playerGame->last_played_at);
        $this->assertEquals($time1, $playerGame->last_unlock_at);
        $this->assertEquals($time1, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($time1, $playerGame->first_unlock_at);
        $this->assertEquals(6, $playerGame->points_total);
        $this->assertEquals(3, $playerGame->points);
        $this->assertEquals(3, $playerGame->points_hardcore);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals(null, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);

        $subsetPlayerGame->refresh();
        $this->assertEquals(3, $subsetPlayerGame->achievements_total);
        $this->assertEquals(1, $subsetPlayerGame->achievements_unlocked);
        $this->assertEquals(1, $subsetPlayerGame->achievements_unlocked_hardcore);
        $this->assertEquals(0, $subsetPlayerGame->achievements_unlocked_softcore);
        $this->assertEquals($time2, $subsetPlayerGame->last_played_at);
        $this->assertEquals($time2, $subsetPlayerGame->last_unlock_at);
        $this->assertEquals($time2, $subsetPlayerGame->last_unlock_hardcore_at);
        $this->assertEquals($time2, $subsetPlayerGame->first_unlock_at);
        $this->assertEquals(9, $subsetPlayerGame->points_total);
        $this->assertEquals(3, $subsetPlayerGame->points);
        $this->assertEquals(3, $subsetPlayerGame->points_hardcore);
        $this->assertEquals(null, $subsetPlayerGame->beaten_at);
        $this->assertEquals(null, $subsetPlayerGame->beaten_hardcore_at);
        $this->assertEquals(null, $subsetPlayerGame->completed_at);
        $this->assertEquals(null, $subsetPlayerGame->completed_hardcore_at);

        $playerAchievementSet->refresh();
        $this->assertEquals(1, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(1, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(0, $playerAchievementSet->achievements_unlocked_softcore);
        $this->assertEquals(0.5, $playerAchievementSet->completion_percentage);
        $this->assertEquals(0.5, $playerAchievementSet->completion_percentage_hardcore);
        $this->assertEquals($time1, $playerAchievementSet->last_unlock_at);
        $this->assertEquals($time1, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time2->diffInSeconds($time1, true), $playerAchievementSet->time_taken);
        $this->assertEquals($time2->diffInSeconds($time1, true), $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(3, $playerAchievementSet->points);
        $this->assertEquals(3, $playerAchievementSet->points_hardcore);
        $this->assertEquals(null, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);

        $subsetPlayerAchievementSet->refresh();
        $this->assertEquals(1, $subsetPlayerAchievementSet->achievements_unlocked);
        $this->assertEquals(1, $subsetPlayerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(0, $subsetPlayerAchievementSet->achievements_unlocked_softcore);
        $this->assertEquals(0.333, round($subsetPlayerAchievementSet->completion_percentage, 3));
        $this->assertEquals(0.333, round($subsetPlayerAchievementSet->completion_percentage_hardcore, 3));
        $this->assertEquals($time2, $subsetPlayerAchievementSet->last_unlock_at);
        $this->assertEquals($time2, $subsetPlayerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time2->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken);
        $this->assertEquals($time2->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken_hardcore);
        $this->assertEquals(3, $subsetPlayerAchievementSet->points);
        $this->assertEquals(3, $subsetPlayerAchievementSet->points_hardcore);
        $this->assertEquals(null, $subsetPlayerAchievementSet->completed_at);
        $this->assertEquals(null, $subsetPlayerAchievementSet->completed_hardcore_at);

        $time3 = $time2->clone()->addMinutes(1);
        Carbon::setTestNow($time3);
        $this->addSoftcoreUnlock($user, $achievements->get(1));

        $time4 = $time3->clone()->addMinutes(1);
        Carbon::setTestNow($time4);
        $this->addSoftcoreUnlock($user, $subsetAchievements->get(1));

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $playerGame->refresh();
        $this->assertEquals(2, $playerGame->achievements_total);
        $this->assertEquals(2, $playerGame->achievements_unlocked);
        $this->assertEquals(1, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(1, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($time4, $playerGame->last_played_at);
        $this->assertEquals($time3, $playerGame->last_unlock_at);
        $this->assertEquals($time1, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($time1, $playerGame->first_unlock_at);
        $this->assertEquals(6, $playerGame->points_total);
        $this->assertEquals(6, $playerGame->points);
        $this->assertEquals(3, $playerGame->points_hardcore);
        $this->assertEquals($time3, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals($time3, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);

        $subsetPlayerGame->refresh();
        $this->assertEquals(3, $subsetPlayerGame->achievements_total);
        $this->assertEquals(2, $subsetPlayerGame->achievements_unlocked);
        $this->assertEquals(1, $subsetPlayerGame->achievements_unlocked_hardcore);
        $this->assertEquals(1, $subsetPlayerGame->achievements_unlocked_softcore);
        $this->assertEquals($time4, $subsetPlayerGame->last_played_at);
        $this->assertEquals($time4, $subsetPlayerGame->last_unlock_at);
        $this->assertEquals($time2, $subsetPlayerGame->last_unlock_hardcore_at);
        $this->assertEquals($time2, $subsetPlayerGame->first_unlock_at);
        $this->assertEquals(9, $subsetPlayerGame->points_total);
        $this->assertEquals(6, $subsetPlayerGame->points);
        $this->assertEquals(3, $subsetPlayerGame->points_hardcore);
        $this->assertEquals(null, $subsetPlayerGame->beaten_at);
        $this->assertEquals(null, $subsetPlayerGame->beaten_hardcore_at);
        $this->assertEquals(null, $subsetPlayerGame->completed_at);
        $this->assertEquals(null, $subsetPlayerGame->completed_hardcore_at);

        $playerAchievementSet->refresh();
        $this->assertEquals(2, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(1, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(1.0, $playerAchievementSet->completion_percentage);
        $this->assertEquals(0.5, $playerAchievementSet->completion_percentage_hardcore);
        $this->assertEquals($time3, $playerAchievementSet->last_unlock_at);
        $this->assertEquals($time1, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time3->diffInSeconds($time1, true), $playerAchievementSet->time_taken); // time capped at completion
        $this->assertEquals($time2->diffInSeconds($time1, true), $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(6, $playerAchievementSet->points);
        $this->assertEquals(3, $playerAchievementSet->points_hardcore);
        $this->assertEquals($time3, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);

        $subsetPlayerAchievementSet->refresh();
        $this->assertEquals(2, $subsetPlayerAchievementSet->achievements_unlocked);
        $this->assertEquals(1, $subsetPlayerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(0.667, round($subsetPlayerAchievementSet->completion_percentage, 3));
        $this->assertEquals(0.333, round($subsetPlayerAchievementSet->completion_percentage_hardcore, 3));
        $this->assertEquals($time4, $subsetPlayerAchievementSet->last_unlock_at);
        $this->assertEquals($time2, $subsetPlayerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time4->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken);
        $this->assertEquals($time2->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken_hardcore);
        $this->assertEquals(6, $subsetPlayerAchievementSet->points);
        $this->assertEquals(3, $subsetPlayerAchievementSet->points_hardcore);
        $this->assertEquals(null, $subsetPlayerAchievementSet->completed_at);
        $this->assertEquals(null, $subsetPlayerAchievementSet->completed_hardcore_at);

        $time5 = $time4->clone()->addMinutes(1);
        Carbon::setTestNow($time5);
        $this->addHardcoreUnlock($user, $achievements->get(1));

        $time6 = $time5->clone()->addMinutes(1);
        Carbon::setTestNow($time6);
        $this->addHardcoreUnlock($user, $subsetAchievements->get(2));

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $playerGame->refresh();
        $this->assertEquals(2, $playerGame->achievements_total);
        $this->assertEquals(2, $playerGame->achievements_unlocked);
        $this->assertEquals(2, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(0, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($time6, $playerGame->last_played_at);
        $this->assertEquals($time3, $playerGame->last_unlock_at);
        $this->assertEquals($time5, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($time1, $playerGame->first_unlock_at);
        $this->assertEquals(6, $playerGame->points_total);
        $this->assertEquals(6, $playerGame->points);
        $this->assertEquals(6, $playerGame->points_hardcore);
        $this->assertEquals($time3, $playerGame->beaten_at);
        $this->assertEquals($time5, $playerGame->beaten_hardcore_at);
        $this->assertEquals($time3, $playerGame->completed_at);
        $this->assertEquals($time5, $playerGame->completed_hardcore_at);

        $subsetPlayerGame->refresh();
        $this->assertEquals(3, $subsetPlayerGame->achievements_total);
        $this->assertEquals(3, $subsetPlayerGame->achievements_unlocked);
        $this->assertEquals(2, $subsetPlayerGame->achievements_unlocked_hardcore);
        $this->assertEquals(1, $subsetPlayerGame->achievements_unlocked_softcore);
        $this->assertEquals($time6, $subsetPlayerGame->last_played_at);
        $this->assertEquals($time6, $subsetPlayerGame->last_unlock_at); // hardcore unlock triggered softcore completion
        $this->assertEquals($time6, $subsetPlayerGame->last_unlock_hardcore_at);
        $this->assertEquals($time2, $subsetPlayerGame->first_unlock_at);
        $this->assertEquals(9, $subsetPlayerGame->points_total);
        $this->assertEquals(9, $subsetPlayerGame->points);
        $this->assertEquals(6, $subsetPlayerGame->points_hardcore);
        $this->assertEquals(null, $subsetPlayerGame->beaten_at); // subset games cannot be beaten
        $this->assertEquals(null, $subsetPlayerGame->beaten_hardcore_at);
        $this->assertEquals($time6, $subsetPlayerGame->completed_at);
        $this->assertEquals(null, $subsetPlayerGame->completed_hardcore_at);

        $playerAchievementSet->refresh();
        $this->assertEquals(2, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(2, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(1.0, $playerAchievementSet->completion_percentage);
        $this->assertEquals(1.0, $playerAchievementSet->completion_percentage_hardcore);
        $this->assertEquals($time3, $playerAchievementSet->last_unlock_at);
        $this->assertEquals($time5, $playerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time3->diffInSeconds($time1, true), $playerAchievementSet->time_taken); // time capped at completion
        $this->assertEquals($time5->diffInSeconds($time1, true), $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(6, $playerAchievementSet->points);
        $this->assertEquals(6, $playerAchievementSet->points_hardcore);
        $this->assertEquals($time3, $playerAchievementSet->completed_at);
        $this->assertEquals($time5, $playerAchievementSet->completed_hardcore_at);

        $subsetPlayerAchievementSet->refresh();
        $this->assertEquals(3, $subsetPlayerAchievementSet->achievements_unlocked);
        $this->assertEquals(2, $subsetPlayerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(1.0, $subsetPlayerAchievementSet->completion_percentage);
        $this->assertEquals(0.667, round($subsetPlayerAchievementSet->completion_percentage_hardcore, 3));
        $this->assertEquals($time6, $subsetPlayerAchievementSet->last_unlock_at); // hardcore unlock triggered softcore completion
        $this->assertEquals($time6, $subsetPlayerAchievementSet->last_unlock_hardcore_at);
        $this->assertEquals($time6->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken);
        $this->assertEquals($time6->diffInSeconds($time1, true), $subsetPlayerAchievementSet->time_taken_hardcore);
        $this->assertEquals(9, $subsetPlayerAchievementSet->points);
        $this->assertEquals(6, $subsetPlayerAchievementSet->points_hardcore);
        $this->assertEquals($time6, $subsetPlayerAchievementSet->completed_at);
        $this->assertEquals(null, $subsetPlayerAchievementSet->completed_hardcore_at);
    }
}
