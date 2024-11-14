<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Enums\AchievementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 3]);
        $achievements = $game->achievements()->published()->get();

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
        $timeTaken = $startedAt->diffInSeconds($lastPlayedAt);
        $timeTakenHardcore = 0;

        $firstUnlockAt = $startedAt;
        $firstUnlockHardcoreAt = 0;
        $lastUnlockAt = $firstUnlockAt;
        $lastUnlockHardcoreAt = 0;

        $this->assertEquals($versionHash, $playerGame->achievement_set_version_hash);
        $this->assertEquals(1, $playerGame->achievements_total);
        $this->assertEquals(1, $playerGame->achievements_unlocked);
        $this->assertEquals(0, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(1, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastPlayedAt, $playerGame->last_played_at);
        $this->assertEquals($timeTaken, $playerGame->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerGame->time_taken_hardcore);
        $this->assertEquals($lastUnlockAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals($firstUnlockHardcoreAt, $playerGame->first_unlock_hardcore_at);
        $this->assertEquals(3, $playerGame->points_total);
        $this->assertEquals(3, $playerGame->points);
        $this->assertEquals(0, $playerGame->points_hardcore);
        $this->assertEquals(3, $playerGame->points_weighted_total);
        $this->assertEquals(0, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals(null, $playerGame->achievements_beat);
        $this->assertEquals(null, $playerGame->achievements_beat_unlocked);
        $this->assertEquals(null, $playerGame->achievements_beat_unlocked_hardcore);
        $this->assertEquals(null, $playerGame->beaten_percentage);
        $this->assertEquals(null, $playerGame->beaten_percentage_hardcore);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals($lastUnlockAt, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);
        $this->assertEquals(1, $playerGame->completion_percentage);
        $this->assertEquals(null, $playerGame->completion_percentage_hardcore);

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

        $this->assertEquals($versionHash, $playerGame->achievement_set_version_hash);
        $this->assertEquals(6, $playerGame->achievements_total);
        $this->assertEquals(5, $playerGame->achievements_unlocked);
        $this->assertEquals(2, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(3, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastPlayedAt, $playerGame->last_played_at);
        $this->assertEquals($timeTaken, $playerGame->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerGame->time_taken_hardcore);
        $this->assertEquals($lastUnlockAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals($firstUnlockHardcoreAt, $playerGame->first_unlock_hardcore_at);
        $this->assertEquals(30, $playerGame->points_total);
        $this->assertEquals(20, $playerGame->points);
        $this->assertEquals(9, $playerGame->points_hardcore);
        $this->assertEquals(30, $playerGame->points_weighted_total);
        $this->assertEquals(9, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals(2, $playerGame->achievements_beat);
        $this->assertEquals(1, $playerGame->achievements_beat_unlocked);
        $this->assertEquals(1, $playerGame->achievements_beat_unlocked_hardcore);
        $this->assertEquals(0.5, $playerGame->beaten_percentage);
        $this->assertEquals(0.5, $playerGame->beaten_percentage_hardcore);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals(null, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);
        $this->assertEquals(5, $playerGame->completion_percentage);
        $this->assertEquals(2, $playerGame->completion_percentage_hardcore);

        $this->addHardcoreUnlock($user, $achievements->get(5)); // Win Condition
        $this->addHardcoreUnlock($user, $achievements->get(1));

        (new UpdateGameMetricsAction())->execute($game);
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);

        $game->refresh();
        $playerGame->refresh();

        $versionHash = $game->achievement_set_version_hash;

        $lastPlayedAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(1)->id)
            ->value('unlocked_hardcore_at');

        $timeTaken = $startedAt->diffInSeconds($lastPlayedAt);
        $timeTakenHardcore = $timeTaken;

        $lastUnlockAt = $lastPlayedAt;
        $lastUnlockHardcoreAt = $lastPlayedAt;

        $beatenAt = PlayerAchievement::where("user_id", $user->id)
            ->where("achievement_id", $achievements->get(5)->id)
            ->value('unlocked_at');

        $beatenHardcoreAt = $beatenAt;

        $this->assertEquals($versionHash, $playerGame->achievement_set_version_hash);
        $this->assertEquals(6, $playerGame->achievements_total);
        $this->assertEquals(6, $playerGame->achievements_unlocked);
        $this->assertEquals(4, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(2, $playerGame->achievements_unlocked_softcore);
        $this->assertEquals($lastPlayedAt, $playerGame->last_played_at);
        $this->assertEquals($timeTaken, $playerGame->time_taken);
        $this->assertEquals($timeTakenHardcore, $playerGame->time_taken_hardcore);
        $this->assertEquals($beatenAt, $playerGame->last_unlock_at);
        $this->assertEquals($lastUnlockHardcoreAt, $playerGame->last_unlock_hardcore_at);
        $this->assertEquals($firstUnlockAt, $playerGame->first_unlock_at);
        $this->assertEquals($firstUnlockHardcoreAt, $playerGame->first_unlock_hardcore_at);
        $this->assertEquals(30, $playerGame->points_total);
        $this->assertEquals(30, $playerGame->points);
        $this->assertEquals(23, $playerGame->points_hardcore);
        $this->assertEquals(30, $playerGame->points_weighted_total);
        $this->assertEquals(23, $playerGame->points_weighted);
        $this->assertEquals($createdAt, $playerGame->created_at);
        $this->assertEquals(2, $playerGame->achievements_beat);
        $this->assertEquals(2, $playerGame->achievements_beat_unlocked);
        $this->assertEquals(2, $playerGame->achievements_beat_unlocked_hardcore);
        $this->assertEquals(1, $playerGame->beaten_percentage);
        $this->assertEquals(1, $playerGame->beaten_percentage_hardcore);
        $this->assertEquals($beatenAt, $playerGame->beaten_at);
        $this->assertEquals($beatenHardcoreAt, $playerGame->beaten_hardcore_at);
        $this->assertEquals($beatenAt, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);
        $this->assertEquals(1, $playerGame->completion_percentage);
        $this->assertEquals(.66666666666667, $playerGame->completion_percentage_hardcore);
    }
}
