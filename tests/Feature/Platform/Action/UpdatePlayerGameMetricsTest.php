<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetrics;
use App\Platform\Actions\UpdatePlayerGameMetrics;
use App\Platform\Enums\AchievementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerGameMetricsTest extends TestCase
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

        (new UpdateGameMetrics())->execute($game);
        (new UpdatePlayerGameMetrics())->execute($playerGame);

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

        $this->assertEquals(
            [
                'achievement_set_version_hash' => $versionHash,
                'achievements_total' => 1,
                'achievements_unlocked' => 1,
                'achievements_unlocked_hardcore' => 0,
                'achievements_unlocked_softcore' => 1,
                'last_played_at' => $lastPlayedAt,
                'time_taken' => $timeTaken,
                'time_taken_hardcore' => $timeTakenHardcore,
                'last_unlock_at' => $lastUnlockAt,
                'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
                'first_unlock_at' => $firstUnlockAt,
                'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
                'points_total' => 3,
                'points' => 3,
                'points_hardcore' => 0,
                'points_weighted_total' => 3,
                'points_weighted' => 0,
                'created_at' => $createdAt,
                'achievements_beat' => null,
                'achievements_beat_unlocked' => null,
                'achievements_beat_unlocked_hardcore' => null,
                'beaten_percentage' => null,
                'beaten_percentage_hardcore' => null,
                'beaten_at' => null,
                'beaten_hardcore_at' => null,
                'completed_at' => $lastUnlockAt,
                'completed_hardcore_at' => null,
                'completion_percentage' => 1,
                'completion_percentage_hardcore' => null,
            ],
            $playerGame->only(
                [
                    'achievement_set_version_hash',
                    'achievements_total',
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                    'last_played_at',
                    'time_taken',
                    'time_taken_hardcore',
                    'last_unlock_at',
                    'last_unlock_hardcore_at',
                    'first_unlock_at',
                    'first_unlock_hardcore_at',
                    'points_total',
                    'points',
                    'points_hardcore',
                    'points_weighted_total',
                    'points_weighted',
                    'created_at',
                    'achievements_beat',
                    'achievements_beat_unlocked',
                    'achievements_beat_unlocked_hardcore',
                    'beaten_percentage',
                    'beaten_percentage_hardcore',
                    'beaten_at',
                    'beaten_hardcore_at',
                    'completed_at',
                    'completed_hardcore_at',
                    'completion_percentage',
                    'completion_percentage_hardcore',
                ]
            )
        );

        Achievement::factory()->published()->count(3)->create(['GameID' => $game->id, 'Points' => 4]);
        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 5, 'type' => AchievementType::Progression]);
        Achievement::factory()->published()->count(1)->create(['GameID' => $game->id, 'Points' => 10, 'type' => AchievementType::WinCondition]);
        $achievements = $game->achievements()->published()->get();

        $this->addSoftcoreUnlock($user, $achievements->get(1));
        $this->addSoftcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $achievements->get(4)); // Progression
        $this->addHardcoreUnlock($user, $achievements->get(3));

        (new UpdateGameMetrics())->execute($game);
        (new UpdatePlayerGameMetrics())->execute($playerGame);

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

        $this->assertEquals(
            [
                'achievement_set_version_hash' => $versionHash,
                'achievements_total' => 6,
                'achievements_unlocked' => 5,
                'achievements_unlocked_hardcore' => 2,
                'achievements_unlocked_softcore' => 3,
                'last_played_at' => $lastPlayedAt,
                'time_taken' => $timeTaken,
                'time_taken_hardcore' => $timeTakenHardcore,
                'last_unlock_at' => $lastUnlockAt,
                'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
                'first_unlock_at' => $firstUnlockAt,
                'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
                'points_total' => 30,
                'points' => 20,
                'points_hardcore' => 9,
                'points_weighted_total' => 30,
                'points_weighted' => 9,
                'created_at' => $createdAt,
                'achievements_beat' => 2,
                'achievements_beat_unlocked' => 1,
                'achievements_beat_unlocked_hardcore' => 1,
                'beaten_percentage' => 0.5,
                'beaten_percentage_hardcore' => 0.5,
                'beaten_at' => null,
                'beaten_hardcore_at' => null,
                'completed_at' => null,
                'completed_hardcore_at' => null,
                'completion_percentage' => 5,
                'completion_percentage_hardcore' => 2,
            ],
            $playerGame->only(
                [
                    'achievement_set_version_hash',
                    'achievements_total',
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                    'last_played_at',
                    'time_taken',
                    'time_taken_hardcore',
                    'last_unlock_at',
                    'last_unlock_hardcore_at',
                    'first_unlock_at',
                    'first_unlock_hardcore_at',
                    'points_total',
                    'points',
                    'points_hardcore',
                    'points_weighted_total',
                    'points_weighted',
                    'created_at',
                    'achievements_beat',
                    'achievements_beat_unlocked',
                    'achievements_beat_unlocked_hardcore',
                    'beaten_percentage',
                    'beaten_percentage_hardcore',
                    'beaten_at',
                    'beaten_hardcore_at',
                    'completed_at',
                    'completed_hardcore_at',
                    'completion_percentage',
                    'completion_percentage_hardcore',
                ]
            )
        );

        $this->addHardcoreUnlock($user, $achievements->get(5)); // Win Condition
        $this->addHardcoreUnlock($user, $achievements->get(1));

        (new UpdateGameMetrics())->execute($game);
        (new UpdatePlayerGameMetrics())->execute($playerGame);

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

        $this->assertEquals(
            [
                'achievement_set_version_hash' => $versionHash,
                'achievements_total' => 6,
                'achievements_unlocked' => 6,
                'achievements_unlocked_hardcore' => 4,
                'achievements_unlocked_softcore' => 2,
                'last_played_at' => $lastPlayedAt,
                'time_taken' => $timeTaken,
                'time_taken_hardcore' => $timeTakenHardcore,
                'last_unlock_at' => $beatenAt,
                'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
                'first_unlock_at' => $firstUnlockAt,
                'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
                'points_total' => 30,
                'points' => 30,
                'points_hardcore' => 23,
                'points_weighted_total' => 30,
                'points_weighted' => 23,
                'created_at' => $createdAt,
                'achievements_beat' => 2,
                'achievements_beat_unlocked' => 2,
                'achievements_beat_unlocked_hardcore' => 2,
                'beaten_percentage' => 1,
                'beaten_percentage_hardcore' => 1,
                'beaten_at' => $beatenAt,
                'beaten_hardcore_at' => $beatenHardcoreAt,
                'completed_at' => $beatenAt,
                'completed_hardcore_at' => null,
                'completion_percentage' => 1,
                'completion_percentage_hardcore' => .66666666666667,
            ],
            $playerGame->only(
                [
                    'achievement_set_version_hash',
                    'achievements_total',
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                    'last_played_at',
                    'time_taken',
                    'time_taken_hardcore',
                    'last_unlock_at',
                    'last_unlock_hardcore_at',
                    'first_unlock_at',
                    'first_unlock_hardcore_at',
                    'points_total',
                    'points',
                    'points_hardcore',
                    'points_weighted_total',
                    'points_weighted',
                    'created_at',
                    'achievements_beat',
                    'achievements_beat_unlocked',
                    'achievements_beat_unlocked_hardcore',
                    'beaten_percentage',
                    'beaten_percentage_hardcore',
                    'beaten_at',
                    'beaten_hardcore_at',
                    'completed_at',
                    'completed_hardcore_at',
                    'completion_percentage',
                    'completion_percentage_hardcore',
                ]
            )
        );
    }
}
