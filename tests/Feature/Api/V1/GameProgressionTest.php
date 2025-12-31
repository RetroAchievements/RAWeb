<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class GameProgressionTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameProgression', ['i' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([]);
    }

    private function createSession(User $user, Game $game, Carbon $startTime, int $durationInSeconds): PlayerSession
    {
        $session = PlayerSession::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'duration' => (int) floor($durationInSeconds / 60),
            'rich_presence_updated_at' => $startTime->clone()->addSeconds($durationInSeconds),
        ]);
        $session->created_at = $startTime;
        $session->updated_at = $session->rich_presence_updated_at;
        $session->save();

        return $session;
    }

    private function achievementData(Achievement $achievement, int $unlockTimesCount,
        int $medianTimeToUnlock, int $unlockHardcoreTimesCount, int $medianTimeToUnlockHardcore): array
    {
        return [
            'ID' => $achievement->id,
            'Title' => $achievement->title,
            'Description' => $achievement->description,
            'Points' => $achievement->points,
            'TrueRatio' => $achievement->points_weighted,
            'Type' => $achievement->type,
            'BadgeName' => $achievement->image_name,
            'NumAwarded' => $achievement->unlocks_total,
            'NumAwardedHardcore' => $achievement->unlocks_hardcore,
            'TimesUsedInUnlockMedian' => $unlockTimesCount,
            'TimesUsedInHardcoreUnlockMedian' => $unlockHardcoreTimesCount,
            'MedianTimeToUnlock' => $medianTimeToUnlock,
            'MedianTimeToUnlockHardcore' => $medianTimeToUnlockHardcore,
        ];
    }

    public function testGetGameProgress(): void
    {
        $game = $this->seedGame(achievements: 4);
        $coreSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $coreSet->achievements_first_published_at = new Carbon('2024-01-07 13:55:51');
        $coreSet->save();

        $achievement1 = $game->achievements->get(0);
        $achievement1->type = 'progression';
        $achievement1->save();

        $achievement2 = $game->achievements->get(1);
        $achievement2->type = 'missable';
        $achievement2->save();

        $achievement3 = $game->achievements->get(2);

        $achievement4 = $game->achievements->get(3);
        $achievement4->type = 'win_condition';
        $achievement4->save();

        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();

        // user1 has all achievements unlocked in hardcore
        $session1a = $this->createSession($user1, $game, new Carbon('2024-01-08 07:13:45'), 3247);
        $this->addHardcoreUnlock($user1, $achievement1, $session1a->created_at->clone()->addSeconds(521));
        $this->addHardcoreUnlock($user1, $achievement2, $session1a->created_at->clone()->addSeconds(633));
        $this->addHardcoreUnlock($user1, $achievement3, $session1a->created_at->clone()->addSeconds(3093));
        $session1b = $this->createSession($user1, $game, new Carbon('2024-01-08 18:43:01'), 759);
        $this->addHardcoreUnlock($user1, $achievement4, $session1b->created_at->clone()->addSeconds(688));

        // pick up updated metrics
        $game->refresh();
        $coreSet->refresh();
        $achievement1->refresh();
        $achievement2->refresh();
        $achievement3->refresh();
        $achievement4->refresh();

        $this->get($this->apiUrl('GetGameProgression', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertExactJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $game->system->id,
                'ConsoleName' => $game->system->name,
                'ImageIcon' => $game->image_icon_asset_path,
                'NumAchievements' => 4,
                'NumDistinctPlayers' => 1,
                'TimesUsedInBeatMedian' => $game->times_beaten,
                'TimesUsedInHardcoreBeatMedian' => $game->times_beaten_hardcore,
                'MedianTimeToBeat' => $game->median_time_to_beat,
                'MedianTimeToBeatHardcore' => $game->median_time_to_beat_hardcore,
                'TimesUsedInCompletionMedian' => $coreSet->times_completed ?? 0,
                'TimesUsedInMasteryMedian' => $coreSet->times_completed_hardcore ?? 0,
                'MedianTimeToComplete' => $coreSet->median_time_to_complete,
                'MedianTimeToMaster' => $coreSet->median_time_to_complete_hardcore,
                'Achievements' => [
                    $this->achievementData($achievement1, 1, 521, 1, 521),
                    $this->achievementData($achievement2, 1, 633, 1, 633),
                    $this->achievementData($achievement3, 1, 3093, 1, 3093),
                    $this->achievementData($achievement4, 1, 3247 + 688, 1, 3247 + 688),
                ],
            ]);

        // user2 has only progression achievements unlocked (in hardcore)
        $session2a = $this->createSession($user2, $game, new Carbon('2024-01-12 19:20:21'), 1897);
        $this->addHardcoreUnlock($user2, $achievement1, $session2a->created_at->clone()->addSeconds(477));
        $this->addHardcoreUnlock($user2, $achievement4, $session2a->created_at->clone()->addSeconds(1883));

        // pick up updated metrics
        $game->refresh();
        $coreSet->refresh();
        $achievement1->refresh();
        $achievement2->refresh();
        $achievement3->refresh();
        $achievement4->refresh();

        $this->get($this->apiUrl('GetGameProgression', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertExactJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $game->system->id,
                'ConsoleName' => $game->system->name,
                'ImageIcon' => $game->image_icon_asset_path,
                'NumAchievements' => 4,
                'NumDistinctPlayers' => 2,
                'TimesUsedInBeatMedian' => $game->times_beaten,
                'TimesUsedInHardcoreBeatMedian' => $game->times_beaten_hardcore,
                'MedianTimeToBeat' => $game->median_time_to_beat,
                'MedianTimeToBeatHardcore' => $game->median_time_to_beat_hardcore,
                'TimesUsedInCompletionMedian' => $coreSet->times_completed ?? 0,
                'TimesUsedInMasteryMedian' => $coreSet->times_completed_hardcore ?? 0,
                'MedianTimeToComplete' => $coreSet->median_time_to_complete,
                'MedianTimeToMaster' => $coreSet->median_time_to_complete_hardcore,
                'Achievements' => [
                    $this->achievementData($achievement1, 2, (int) floor((477 + 521) / 2), 2, (int) floor((477 + 521) / 2)),
                    $this->achievementData($achievement2, 1, 633, 1, 633),
                    $this->achievementData($achievement4, 2, (int) floor((3247 + 688 + 1883) / 2), 2, (int) floor((3247 + 688 + 1883) / 2)),
                    $this->achievementData($achievement3, 1, 3093, 1, 3093),
                ],
            ]);

        // user3 has non-hardcore unlocks
        $session3a = $this->createSession($user3, $game, new Carbon('2024-01-14 01:01:53'), 673);
        $this->addSoftcoreUnlock($user3, $achievement1, $session3a->created_at->clone()->addSeconds(613));
        $session3b = $this->createSession($user3, $game, new Carbon('2024-01-14 09:31:44'), 217);
        $this->addSoftcoreUnlock($user3, $achievement3, $session3b->created_at->clone()->addSeconds(148));
        $session3c = $this->createSession($user3, $game, new Carbon('2024-01-15 01:17:21'), 946);
        $this->addSoftcoreUnlock($user3, $achievement4, $session3c->created_at->clone()->addSeconds(511));
        $this->addSoftcoreUnlock($user3, $achievement2, $session3c->created_at->clone()->addSeconds(909));

        // pick up updated metrics
        $game->refresh();
        $coreSet->refresh();
        $achievement1->refresh();
        $achievement2->refresh();
        $achievement3->refresh();
        $achievement4->refresh();

        $this->get($this->apiUrl('GetGameProgression', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertExactJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $game->system->id,
                'ConsoleName' => $game->system->name,
                'ImageIcon' => $game->image_icon_asset_path,
                'NumAchievements' => 4,
                'NumDistinctPlayers' => 3,
                'TimesUsedInBeatMedian' => $game->times_beaten,
                'TimesUsedInHardcoreBeatMedian' => $game->times_beaten_hardcore,
                'MedianTimeToBeat' => $game->median_time_to_beat,
                'MedianTimeToBeatHardcore' => $game->median_time_to_beat_hardcore,
                'TimesUsedInCompletionMedian' => $coreSet->times_completed ?? 0,
                'TimesUsedInMasteryMedian' => $coreSet->times_completed_hardcore ?? 0,
                'MedianTimeToComplete' => $coreSet->median_time_to_complete,
                'MedianTimeToMaster' => $coreSet->median_time_to_complete_hardcore,
                'Achievements' => [
                    $this->achievementData($achievement1, 3, 521 /* 477,521,613 */, 2, (int) floor((477 + 521) / 2)),
                    $this->achievementData($achievement2, 2, (int) floor((633 + 673 + 217 + 909) / 2), 1, 633),
                    $this->achievementData($achievement4, 3, 1883 /* 673 + 217 + 511, 1883, 3247 + 688 */, 2, (int) floor((3247 + 688 + 1883) / 2)),
                    $this->achievementData($achievement3, 2, (int) floor((3093 + 673 + 148) / 2), 1, 3093),
                ],
            ]);

        // request hardcore players ignores user3
        $this->get($this->apiUrl('GetGameProgression', ['i' => $game->id, 'h' => 1]))
            ->assertSuccessful()
            ->assertExactJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $game->system->id,
                'ConsoleName' => $game->system->name,
                'ImageIcon' => $game->image_icon_asset_path,
                'NumAchievements' => 4,
                'NumDistinctPlayers' => 3,
                'TimesUsedInBeatMedian' => $game->times_beaten,
                'TimesUsedInHardcoreBeatMedian' => $game->times_beaten_hardcore,
                'MedianTimeToBeat' => $game->median_time_to_beat,
                'MedianTimeToBeatHardcore' => $game->median_time_to_beat_hardcore,
                'TimesUsedInCompletionMedian' => $coreSet->times_completed ?? 0,
                'TimesUsedInMasteryMedian' => $coreSet->times_completed_hardcore ?? 0,
                'MedianTimeToComplete' => $coreSet->median_time_to_complete,
                'MedianTimeToMaster' => $coreSet->median_time_to_complete_hardcore,
                'Achievements' => [
                    $this->achievementData($achievement1, 2, (int) floor((477 + 521) / 2), 2, (int) floor((477 + 521) / 2)),
                    $this->achievementData($achievement2, 1, 633, 1, 633),
                    $this->achievementData($achievement4, 2, (int) floor((3247 + 688 + 1883) / 2), 2, (int) floor((3247 + 688 + 1883) / 2)),
                    $this->achievementData($achievement3, 1, 3093, 1, 3093),
                ],
            ]);
    }
}
