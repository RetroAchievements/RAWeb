<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);

class SubmitLeaderboardEntryTestHelpers
{
    public static function buildValidationHash(Leaderboard $leaderboard, User $user, int $score, int $offset = 0): string
    {
        $data = $leaderboard->id . $user->username . $score;
        if ($offset > 0) {
            $data .= $offset;
        }

        return md5($data);
     }

    public static function buildLBData(Leaderboard $leaderboard): array
    {
        return [
            'LeaderboardID' => $leaderboard->id,
            'Title' => $leaderboard->title,
            'Format' => $leaderboard->format,
            'LowerIsBetter' => $leaderboard->rank_asc,
            'GameID' => $leaderboard->game_id,
        ];
    }

    public static function buildEntry(int $rank, User $user, int $score, Carbon $when): array
    {
        return [
            'Rank' => $rank,
            'User' => $user->display_name,
            'Score' => $score,
            'DateSubmitted' => $when->unix(),
        ];
    }

    private static function assignRanks(array &$entries): void
    {
        $i = 1;
        $rank = 1;
        $prev_score = null;
        foreach ($entries as &$entry) {
            if ($entry['Score'] !== $prev_score) {
                $prev_score = $entry['Score'];
                $rank = $i;
            }

            $entry['Rank'] = $rank;
            $i++;
        }
    }

    public static function updateRanks(array &$entries, bool $lowerIsBetter): void
    {
        usort($entries, function ($a, $b) use ($lowerIsBetter) {
            if ($a['Score'] !== $b['Score']) {
                return $b['Score'] - $a['Score'];
            }

            if ($lowerIsBetter) {
                return $b['DateSubmitted'] - $a['DateSubmitted'];
            } else {
                return $a['DateSubmitted'] - $b['DateSubmitted'];
            }
        });

        if ($lowerIsBetter) {
            $entries = array_reverse($entries);
        }

        SubmitLeaderboardEntryTestHelpers::assignRanks($entries);
    }

    public static function updateRanksUnsigned(array &$entries, bool $lowerIsBetter): void
    {
        usort($entries, function ($a, $b) use ($lowerIsBetter) {
            if ($a['Score'] !== $b['Score']) {
                $aScore = $a['Score'];
                if ($aScore < 0) {
                    $aScore += 0x100000000;
                }
                $bScore = $b['Score'];
                if ($bScore < 0) {
                    $bScore += 0x100000000;
                }

                return $bScore - $aScore;
            }

            if ($lowerIsBetter) {
                return $b['DateSubmitted'] - $a['DateSubmitted'];
            } else {
                return $a['DateSubmitted'] - $b['DateSubmitted'];
            }
        });

        if ($lowerIsBetter) {
            $entries = array_reverse($entries);
        }

        SubmitLeaderboardEntryTestHelpers::assignRanks($entries);
    }

    public static function createEmptyLeaderboard(): array
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var GameAchievementSet $gameAchievementSet */
        $gameAchievementSet = GameAchievementSet::factory()->create(['game_id' => $game->id]);
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
        /** @var GameHash $gameHash */
        $gameHash = GameHash::create([
            'game_id' => $game->id,
            'system_id' => $game->system_id,
            'compatibility' => GameHashCompatibility::Compatible,
            'md5' => fake()->md5,
            'name' => 'hash_' . $game->id,
            'description' => 'hash_' . $game->id,
        ]);

        return [
            'leaderboard' => $leaderboard,
            'gameHash' => $gameHash->md5,
        ];
    }

    public static function createLowerIsBetterLeaderboard(): array
    {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];

        $data['entries'] = [];
        for ($rank = 1; $rank <= 5; $rank++) {
            $user = User::factory()->create();
            $score = $rank * 10000;
            $timestamp = Carbon::now()->subHours($score % 37);
            $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry($rank, $user, $score, $timestamp);

            $entry = LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'score' => $score,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            if ($rank === 1) {
                $leaderboard->top_entry_id = $entry->id;
            }
        }

        $leaderboard->rank_asc = true;
        $leaderboard->save();
        $leaderboard->refresh(); // save causes top_entry_id to be set to null, then updated out of context, leaving it as null here

        return $data;
    }

    public static function createHigherIsBetterLeaderboard(): array
    {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];

        $data['entries'] = [];
        for ($rank = 1; $rank <= 5; $rank++) {
            $user = User::factory()->create();
            $score = (6 - $rank) * 10000;
            $timestamp = Carbon::now()->subHours($score % 37);
            $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry($rank, $user, $score, $timestamp);

            $entry = LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'score' => $score,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            if ($rank === 1) {
                $leaderboard->top_entry_id = $entry->id;
            }
        }

        $leaderboard->rank_asc = false;
        $leaderboard->save();
        $leaderboard->refresh(); // save causes top_entry_id to be set to null, then updated out of context, leaving it as null here

        return $data;
    }

    public static function createUnsignedLeaderboard(): array
    {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];
        $leaderboard->format = ValueFormat::ValueUnsigned;

        $data['entries'] = [];
        for ($rank = 1; $rank <= 4; $rank++) {
            $user = User::factory()->create();
            $score = (5 - $rank) * 1_000_000_000;
            if ($score >= 0x80000000) {
                $score -= 0x100000000; // convert to signed
            }
            $timestamp = Carbon::now()->subHours($score % 37);
            $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry($rank, $user, $score, $timestamp);

            $entry = LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'score' => $score,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            if ($rank === 1) {
                $leaderboard->top_entry_id = $entry->id;
            }
        }

        $leaderboard->rank_asc = false;
        $leaderboard->save();
        $leaderboard->refresh(); // save causes top_entry_id to be set to null, then updated out of context, leaving it as null here

        return $data;
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();
});

describe('new submission', function () {
    test('is only (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];
        $score = 55555;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 1,
                        'Rank' => 1,
                    ],
                    'TopEntries' => [
                        SubmitLeaderboardEntryTestHelpers::buildEntry(1, $this->user, $score, Carbon::now()),
                    ],
                ],
            ]);

        $entry = LeaderboardEntry::where('user_id', $this->user->id)->where('leaderboard_id', $leaderboard->id)->first();
        $leaderboard->refresh();
        $this->assertEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('is best (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 99;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 1,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $entry = LeaderboardEntry::where('user_id', $this->user->id)->where('leaderboard_id', $leaderboard->id)->first();
        $leaderboard->refresh();
        $this->assertEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('is best (higher is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 99999;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 1,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $entry = LeaderboardEntry::where('user_id', $this->user->id)->where('leaderboard_id', $leaderboard->id)->first();
        $leaderboard->refresh();
        $this->assertEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('is worst (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 99999;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 6,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $entry = LeaderboardEntry::where('user_id', $this->user->id)->where('leaderboard_id', $leaderboard->id)->first();
        $leaderboard->refresh();
        $this->assertNotEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('is worst (higher is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 99;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 6,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $entry = LeaderboardEntry::where('user_id', $this->user->id)->where('leaderboard_id', $leaderboard->id)->first();
        $leaderboard->refresh();
        $this->assertNotEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('is tied (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $score = 30000;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3, // rank 3 is shared, but entry should be index 4
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('is tied (higher is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $score = 30000;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('with unranked', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $otherEntry = $leaderboard->entries()->where('score', 40000)->with('user')->first();
        $otherEntry->user->unranked_at = Carbon::now()->subDays(2);
        $otherEntry->user->save();
        $data['entries'] = array_filter($data['entries'], function ($entry) use ($otherEntry) {
            return $entry['User'] != $otherEntry->user->display_name;
        });

        $score = 33333;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 4,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('as unranked', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $this->user->unranked_at = Carbon::now()->subDays(2);
        $this->user->save();

        $score = 33333;
        // user should not be in the entries list

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 4, // this would be the user's rank
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('without game hash', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];
        $score = 55555;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 1,
                        'Rank' => 1,
                    ],
                    'TopEntries' => [
                        SubmitLeaderboardEntryTestHelpers::buildEntry(1, $this->user, $score, Carbon::now()),
                    ],
                ],
            ]);
    });

    test('unknown leaderboard', function () {
        $score = 55555;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => 8888, 's' => $score]))
            ->assertStatus(404)
            ->assertExactJson([
                'Code' => 'not_found',
                'Status' => 404,
                'Success' => false,
                'Error' => 'Unknown leaderboard.',
            ]);
    });

    test('inactive system', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createEmptyLeaderboard();
        $leaderboard = $data['leaderboard'];

        $leaderboard->loadMissing('game.system');
        $system = $leaderboard->game->system;
        $system->active = false;
        $system->save();

        $score = 55555;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'unsupported_system',
                'Status' => 403,
                'Success' => false,
                'Error' => 'Cannot submit leaderboard entries for unsupported console.',
            ]);
    });
});

describe('repeat submission', function () {
    test('is worse (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $bestScore, $bestTimestamp);
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $score = 44444;
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $bestScore,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 4,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('is worse (higher is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $bestScore, $bestTimestamp);
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: false);

        $score = 22222;
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $bestScore,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('is better (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $score = 22222;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('is better (higher is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];
        $topEntryId = $leaderboard->top_entry_id;

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $score = 44444;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 2,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($topEntryId, $leaderboard->top_entry_id);
    });

    test('is best (lower is better)', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        $entry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $score = 99;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 1,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $leaderboard->refresh();
        $this->assertEquals($entry->id, $leaderboard->top_entry_id);
    });

    test('delete and submit worse', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $originalScore = 33333;
        $originalTimestamp = Carbon::now()->subHours($originalScore % 37);

        $entry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $originalScore,
            'created_at' => $originalTimestamp,
            'updated_at' => $originalTimestamp,
        ]);
        $entry->delete();

        $score = 44444;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 5,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);

        $entry->refresh();
        $this->assertNull($entry->deleted_at); // soft-deleted entry should be reused
        $this->assertEquals($score, $entry->score);
    });
});

describe('unsigned', function () {
    test('new submission', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createUnsignedLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 3_500_000_000 - 0x100000000;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanksUnsigned($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 2,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('submit better', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createUnsignedLeaderboard();
        $leaderboard = $data['leaderboard'];

        $bestScore = 2_800_000_000 - 0x100000000;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $score = 3_500_000_000 - 0x100000000;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanksUnsigned($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 2,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('submit worse', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createUnsignedLeaderboard();
        $leaderboard = $data['leaderboard'];

        $bestScore = 3_500_000_000 - 0x100000000;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $score = 2_800_000_000 - 0x100000000;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $bestScore, $bestTimestamp);
        SubmitLeaderboardEntryTestHelpers::updateRanksUnsigned($data['entries'], lowerIsBetter: false);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $bestScore,
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 2,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });
});

describe('backdated', function () {
    test('new submission', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $offset = 345;
        $backdateTimestamp = Carbon::now()->subSeconds($offset);

        $score = 33333;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, $backdateTimestamp);
        SubmitLeaderboardEntryTestHelpers::updateRanksUnsigned($data['entries'], lowerIsBetter: false);

        $validationHash = SubmitLeaderboardEntryTestHelpers::buildValidationHash($leaderboard, $this->user, $score, $offset);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', [
                'i' => $leaderboard->id,
                's' => $score,
                'm' => $data['gameHash'],
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('negative offset is ignored', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createHigherIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $offset = -345;
        $backdateTimestamp = Carbon::now()->subSeconds($offset);

        $score = 33333;
        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $score, Carbon::now());
        SubmitLeaderboardEntryTestHelpers::updateRanksUnsigned($data['entries'], lowerIsBetter: false);

        $validationHash = SubmitLeaderboardEntryTestHelpers::buildValidationHash($leaderboard, $this->user, $score, $offset);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('submitlbentry', [
                'i' => $leaderboard->id,
                's' => $score,
                'm' => $data['gameHash'],
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $score,
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 3,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });
});

describe('validation', function () {
    test('no user agent', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 22222;
        // don't need to update entries as the submission won't actually go through

        $this->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => 0, // not actually submitted
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 1, // always 1 when not actually submitted
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('unknown user agent', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 22222;
        // don't need to update entries as the submission won't actually go through

        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => 0, // not actually submitted
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 1, // always 1 when not actually submitted
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('outdated user agent', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 22222;
        // don't need to update entries as the submission won't actually go through

        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => 0, // not actually submitted
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 1, // always 1 when not actually submitted
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('unsupported user agent', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 22222;
        // don't need to update entries as the submission won't actually go through

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => 0, // not actually submitted
                    'RankInfo' => [
                        'NumEntries' => 5,
                        'Rank' => 1, // always 1 when not actually submitted
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('unsupported user agent improved score', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $bestScore = 33333;
        $bestTimestamp = Carbon::now()->subHours($bestScore % 37);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $this->user->id,
            'score' => $bestScore,
            'created_at' => $bestTimestamp,
            'updated_at' => $bestTimestamp,
        ]);

        $data['entries'][] = SubmitLeaderboardEntryTestHelpers::buildEntry(0, $this->user, $bestScore, $bestTimestamp);
        SubmitLeaderboardEntryTestHelpers::updateRanks($data['entries'], lowerIsBetter: true);

        $score = 22222;

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'Score' => $score,
                    'BestScore' => $bestScore, // previous best returned
                    'RankInfo' => [
                        'NumEntries' => 6,
                        'Rank' => 4,
                    ],
                    'TopEntries' => $data['entries'],
                ],
            ]);
    });

    test('blocked user agent', function () {
        $data = SubmitLeaderboardEntryTestHelpers::createLowerIsBetterLeaderboard();
        $leaderboard = $data['leaderboard'];

        $score = 22222;
        // don't need to update entries as the submission won't actually go through

        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('submitlbentry', ['i' => $leaderboard->id, 's' => $score, 'm' => $data['gameHash']]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'unsupported_client',
                'Status' => 403,
                'Success' => false,
                'Error' => 'This client is not supported.',
            ]);
    });
});
