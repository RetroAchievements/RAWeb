<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\UserRelationship;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Models\UserRelation;
use App\Platform\Enums\ValueFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubmitLeaderboardEntryTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    private function buildLBData(Leaderboard $leaderboard): array
    {
        return [
            'LeaderboardID' => $leaderboard->ID,
            'Title' => $leaderboard->Title,
            'Format' => $leaderboard->Format,
            'LowerIsBetter' => $leaderboard->LowerIsBetter,
            'GameID' => $leaderboard->GameID,
        ];
    }

    private function buildEntry(int $rank, User $user, int $score, Carbon $when): array
    {
        return [
            'Rank' => $rank,
            'User' => $user->User,
            'Score' => $score,
            'DateSubmitted' => $when->unix(),
        ];
    }

    public function testSubmitLeaderboardEntryEmptyLeaderboard(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var Game $game */
        $game = Game::factory()->create();
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);

        // first submission
        $score = $bestScore = 55555;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 1,
                        'Rank' => 1,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                ],
            ]);

        // worse submission
        $now2 = $now->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now2);

        $score = 44444;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 1,
                        'Rank' => 1,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                ],
            ]);

        // better submission
        $now3 = $now2->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now3);

        $score = $bestScore = 66666;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 1,
                        'Rank' => 1,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now3),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now3),
                    ],
                ],
            ]);
    }

    public function testSubmitLeaderboardEntryMany(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();

        /** @var Game $game */
        $game = Game::factory()->create();
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);

        $timestamps = [];
        for ($i = 0; $i < 15; $i++) {
            $score = 3456 + (pow($i, 5) % 3853);
            Carbon::setTestNow($now->clone()->subMinutes(pow($i + 1, 3) % 367)->startOfSecond());

            $user = User::factory()->create();
            LeaderboardEntry::create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'score' => $score,
            ]);

            $timestamps[$user->id] = Carbon::now()->clone();
        }

        // create duplicate entry for score 6581
        $user = User::factory()->create();
        LeaderboardEntry::create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => 6581,
        ]);
        $timestamps[$user->id] = Carbon::now()->clone();

        $user = User::factory()->create(['Untracked' => 1]);
        LeaderboardEntry::create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => 7000,
        ]);
        $timestamps[$user->id] = Carbon::now()->clone();

        $addFollowing = function ($id) {
            UserRelation::create([
                'user_id' => $this->user->ID,
                'related_user_id' => $id,
                'Friendship' => UserRelationship::Following,
            ]);
        };
        $addFollowing(2);
        $addFollowing(3);
        $addFollowing(6);
        $addFollowing(7);
        $addFollowing(8);
        $addFollowing(9);
        $addFollowing(14);
        $addFollowing(16);
        $addFollowing(18);

        // leaderboard state:
        //  rank user_id score
        //     1      12  7131
        //            18  7000 - friend, untracked
        //     2       7  6581 - friend
        //     2      17  6581
        //     4      13  6534
        //     5      16  5713 - friend
        //     6      14  5696 - friend
        //     7      10  5400
        //     8      15  4861
        //     9       9  4851 - friend
        //    10      11  4710
        //    11       6  4480 - friend
        //    12       5  3699
        //    13       8  3526 - friend
        //    14       4  3488
        //    15       3  3457 - friend
        //    16       2  3456 - friend

        // first submission
        Carbon::setTestNow($now);
        $score = $bestScore = 6000; // this should make the player rank 5
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 17,
                        'Rank' => 5,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, User::find(12), 7131, $timestamps[12]),
                        $this->buildEntry(2, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(2, User::find(17), 6581, $timestamps[17]),
                        $this->buildEntry(4, User::find(13), 6534, $timestamps[13]),
                        $this->buildEntry(5, $this->user, 6000, $now),
                        $this->buildEntry(6, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(7, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(8, User::find(10), 5400, $timestamps[10]),
                        $this->buildEntry(9, User::find(15), 4861, $timestamps[15]),
                        $this->buildEntry(10, User::find(9), 4851, $timestamps[9]),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(2, $this->user, 6000, $now),
                        $this->buildEntry(3, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(4, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(5, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(6, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(7, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(8, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(9, User::find(2), 3456, $timestamps[2]),
                    ],
                ],
            ]);

        // worse submission
        $now2 = $now->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now2);

        $score = 5300;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 17,
                        'Rank' => 5,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, User::find(12), 7131, $timestamps[12]),
                        $this->buildEntry(2, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(2, User::find(17), 6581, $timestamps[17]),
                        $this->buildEntry(4, User::find(13), 6534, $timestamps[13]),
                        $this->buildEntry(5, $this->user, 6000, $now),
                        $this->buildEntry(6, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(7, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(8, User::find(10), 5400, $timestamps[10]),
                        $this->buildEntry(9, User::find(15), 4861, $timestamps[15]),
                        $this->buildEntry(10, User::find(9), 4851, $timestamps[9]),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(2, $this->user, 6000, $now),
                        $this->buildEntry(3, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(4, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(5, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(6, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(7, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(8, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(9, User::find(2), 3456, $timestamps[2]),
                    ],
                ],
            ]);

        // better submission
        $now3 = $now2->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now3);

        $score = $bestScore = 7000; // this should make the player rank 2
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 17,
                        'Rank' => 2,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, User::find(12), 7131, $timestamps[12]),
                        $this->buildEntry(2, $this->user, 7000, $now3),
                        $this->buildEntry(3, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(3, User::find(17), 6581, $timestamps[17]),
                        $this->buildEntry(5, User::find(13), 6534, $timestamps[13]),
                        $this->buildEntry(6, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(7, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(8, User::find(10), 5400, $timestamps[10]),
                        $this->buildEntry(9, User::find(15), 4861, $timestamps[15]),
                        $this->buildEntry(10, User::find(9), 4851, $timestamps[9]),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, 7000, $now3),
                        $this->buildEntry(2, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(3, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(4, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(5, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(6, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(7, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(8, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(9, User::find(2), 3456, $timestamps[2]),
                    ],
                ],
            ]);

        // lower is better
        $leaderboard->LowerIsBetter = 1;
        $leaderboard->save();

        $now4 = $now3->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now4);

        $score = 7100;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 17,
                        'Rank' => 16,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, User::find(2), 3456, $timestamps[2]),
                        $this->buildEntry(2, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(3, User::find(4), 3488, $timestamps[4]),
                        $this->buildEntry(4, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(5, User::find(5), 3699, $timestamps[5]),
                        $this->buildEntry(6, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(7, User::find(11), 4710, $timestamps[11]),
                        $this->buildEntry(8, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(9, User::find(15), 4861, $timestamps[15]),
                        $this->buildEntry(10, User::find(10), 5400, $timestamps[10]),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, User::find(2), 3456, $timestamps[2]),
                        $this->buildEntry(2, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(3, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(4, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(5, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(6, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(7, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(8, User::find(7), 6581, $timestamps[7]),
                        $this->buildEntry(9, $this->user, 7000, $now3),
                    ],
                ],
            ]);

        // better score, tied with 4th place
        $now5 = $now4->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now5);

        $score = $bestScore = 3526;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 17,
                        'Rank' => 4,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, User::find(2), 3456, $timestamps[2]),
                        $this->buildEntry(2, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(3, User::find(4), 3488, $timestamps[4]),
                        $this->buildEntry(4, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(4, $this->user, 3526, $now5),
                        $this->buildEntry(6, User::find(5), 3699, $timestamps[5]),
                        $this->buildEntry(7, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(8, User::find(11), 4710, $timestamps[11]),
                        $this->buildEntry(9, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(10, User::find(15), 4861, $timestamps[15]),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, User::find(2), 3456, $timestamps[2]),
                        $this->buildEntry(2, User::find(3), 3457, $timestamps[3]),
                        $this->buildEntry(3, User::find(8), 3526, $timestamps[8]),
                        $this->buildEntry(3, $this->user, 3526, $now5),
                        $this->buildEntry(5, User::find(6), 4480, $timestamps[6]),
                        $this->buildEntry(6, User::find(9), 4851, $timestamps[9]),
                        $this->buildEntry(7, User::find(14), 5696, $timestamps[14]),
                        $this->buildEntry(8, User::find(16), 5713, $timestamps[16]),
                        $this->buildEntry(9, User::find(7), 6581, $timestamps[7]),
                    ],
                ],
            ]);
    }

    public function testSubmitLeaderboardEntryUnsigned(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();

        /** @var Game $game */
        $game = Game::factory()->create();
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id, 'Format' => ValueFormat::ValueUnsigned]);

        $oneBillion = 1_000_000_000; // signed
        $twoBillion = 2_000_000_000; // signed
        $threeBillion = -1_294_967_296; // 3 billion is 0xB2D05E00, which is -1294967296

        $time1 = $now->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($time1);
        $user1 = User::factory()->create(['User' => 'user1']);
        LeaderboardEntry::create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => $twoBillion,
        ]);

        $time2 = $time1->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($time2);
        $user2 = User::factory()->create(['User' => 'user2']);
        LeaderboardEntry::create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => $threeBillion,
        ]);

        // leaderboard state:
        //  rank user_id score
        //     1       2  $threeBillion
        //     2       1  $twoBillion

        // first submission
        Carbon::setTestNow($now);
        $score = $bestScore = $oneBillion;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 3,
                        'Rank' => 3,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $user2, $threeBillion, $time2),
                        $this->buildEntry(2, $user1, $twoBillion, $time1),
                        $this->buildEntry(3, $this->user, $bestScore, $now),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                ],
            ]);

        // worse submission
        $now2 = $now->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now2);

        $score = 800_000_000;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 3,
                        'Rank' => 3,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $user2, $threeBillion, $time2),
                        $this->buildEntry(2, $user1, $twoBillion, $time1),
                        $this->buildEntry(3, $this->user, $bestScore, $now),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now),
                    ],
                ],
            ]);

        // better submission
        $now3 = $now2->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now3);

        // 2.5million is 0x9502F900, which is -1794967296
        $score = $bestScore = -1_794_967_296; // this should make the player rank 2
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 3,
                        'Rank' => 2,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $user2, $threeBillion, $time2),
                        $this->buildEntry(2, $this->user, $bestScore, $now3),
                        $this->buildEntry(3, $user1, $twoBillion, $time1),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now3),
                    ],
                ],
            ]);

        // lower is better
        $leaderboard->LowerIsBetter = 1;
        $leaderboard->save();

        $now4 = $now3->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now4);

        $score = -1_594_967_296; // 2.7million -> 0xA0EEBB00 -> -1594967296
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 3,
                        'Rank' => 2,
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $user1, $twoBillion, $time1),
                        $this->buildEntry(2, $this->user, $bestScore, $now3),
                        $this->buildEntry(3, $user2, $threeBillion, $time2),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now3),
                    ],
                ],
            ]);

        // better score, tied with first place
        $now5 = $now4->clone()->addMinutes(5)->startOfSecond();
        Carbon::setTestNow($now5);

        $score = $bestScore = $twoBillion;
        $this->post('dorequest.php', $this->apiParams('submitlbentry', ['i' => $leaderboard->ID, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Success' => true,
                    'Score' => $score,
                    'ScoreFormatted' => ValueFormat::format($score, $leaderboard->Format),
                    'BestScore' => $bestScore,
                    'LBData' => $this->buildLBData($leaderboard),
                    'RankInfo' => [
                        'NumEntries' => 3,
                        'Rank' => 1, // shared rank
                    ],
                    'TopEntries' => [
                        $this->buildEntry(1, $user1, $twoBillion, $time1),
                        $this->buildEntry(1, $this->user, $bestScore, $now5),
                        $this->buildEntry(3, $user2, $threeBillion, $time2),
                    ],
                    'TopEntriesFriends' => [
                        $this->buildEntry(1, $this->user, $bestScore, $now5),
                    ],
                ],
            ]);
    }
}
