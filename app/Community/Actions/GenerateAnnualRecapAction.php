<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Mail\AnnualRecapMail;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GenerateAnnualRecapAction
{
    public function execute(User $user): void
    {
        if (!$user->EmailAddress) {
            return;
        }

        $year = Carbon::now()->subMonths(6)->year;
        $january = $startDate = Carbon::create($year, 1, 1, 0, 0, 0);
        $endDate = Carbon::create($year + 1, 1, 1, 0, 0, 0);

        $gameData = $this->getGameData($user, $startDate, $endDate);

        // don't bother generating recap if the player has less than 10 hours of playtime for the year
        $totalDuration = 0;
        foreach ($gameData as $game) {
            $totalDuration += $game['totalDuration'];
        }
        if ($totalDuration < 600) {
            return;
        }

        $recapData = [
            'year' => $year,
        ];

        $this->summarizeUnlocks($recapData, $user, $gameData, $startDate, $endDate);
        $this->summarizePlayTime($recapData, $gameData);
        $this->summarizeAwards($recapData, $user, $startDate, $endDate);
        $this->determineMostPlayedGame($recapData, $gameData);
        $this->determineRarestAchievement($recapData, $user, $startDate, $endDate);
        $this->summarizePosts($recapData, $user, $startDate, $endDate);
        $this->summarizeDevelopment($recapData, $user, $startDate, $endDate);

        Mail::to($user->EmailAddress)->queue(
            new AnnualRecapMail($user, $recapData)
        );
    }

    private function getGameData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $games = PlayerSession::where('user_id', $user->id)
            ->where('duration', '>=', 5)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->join('GameData', 'GameData.ID', '=', 'game_id')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->groupBy('game_id')
            ->select([
                'GameData.ID',
                'GameData.ConsoleID',
                DB::raw('sum(duration) as totalDuration'),
            ]);

        $gameData = [];
        foreach ($games->get() as $game) {
            $gameData[$game->ID] = [
                'ConsoleID' => $game->ConsoleID,
                'totalDuration' => (int) $game->totalDuration,
            ];
        }

        return $gameData;
    }

    private function summarizeUnlocks(array &$recapData, User $user, array $gameData, Carbon $startDate, Carbon $endDate): void
    {
        $gameIds = array_keys($gameData);

        $hardcoreTally = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $startDate)
            ->where('unlocked_hardcore_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->whereIn('Achievements.GameID', $gameIds)
            ->where('Achievements.Flags', AchievementFlag::OfficialCore)
            ->select(
                DB::raw('count(*) as count'),
                DB::raw('sum(Achievements.Points) as points'),
            )
            ->first();

        $softcoreTally = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->whereNull('unlocked_hardcore_at')
            ->where('unlocked_at', '>=', $startDate)
            ->where('unlocked_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->whereIn('Achievements.GameID', $gameIds)
            ->where('Achievements.Flags', AchievementFlag::OfficialCore)
            ->select(
                DB::raw('count(*) as count'),
                DB::raw('sum(Achievements.Points) as points'),
            )
            ->first();

        $numLeaderboards = LeaderboardEntry::where('user_id', $user->id)
            ->where('updated_at', '>=', $startDate)
            ->where('updated_at', '<', $endDate)
            ->count();

        $recapData['gamesPlayed'] = count($gameData);
        $recapData['achievementsUnlocked'] = $hardcoreTally->count + $softcoreTally->count;
        $recapData['hardcorePointsEarned'] = $hardcoreTally->points;
        $recapData['softcorePointsEarned'] = $softcoreTally->points;
        $recapData['leaderboardsSubmitted'] = $numLeaderboards;
    }

    private function summarizePlayTime(array &$recapData, array $gameData): void
    {
        $totalTime = 0;
        $systemTimes = [];
        $mostPlayedSystem = 0;
        foreach ($gameData as $id => $game) {
            $updatedTime = ($systemTimes[$game['ConsoleID']] ?? 0) + $game['totalDuration'];
            $systemTimes[$game['ConsoleID']] = $updatedTime;
            $totalTime += $game['totalDuration'];

            if ($mostPlayedSystem !== $game['ConsoleID']) {
                if ($mostPlayedSystem === 0 || $updatedTime > $systemTimes[$mostPlayedSystem]) {
                    $mostPlayedSystem = $game['ConsoleID'];
                }
            }
        }

        $recapData['totalPlaytime'] = $this->hoursMinutes($totalTime);
        $recapData['playedSystems'] = count($systemTimes);

        $system = System::find($mostPlayedSystem);
        if ($system) {
            $recapData['mostPlayedSystem'] = $system->Name;
            $recapData['mostPlayedSystemPlaytime'] = $this->hoursMinutes($systemTimes[$mostPlayedSystem]);
        } else {
            $recapData['mostPlayedSystem'] = '';
            $recapData['mostPlayedSystemPlaytime'] = '';
        }
    }

    private function summarizeAwards(array &$recapData, User $user, Carbon $startDate, Carbon $endDate): void
    {
        $awards = PlayerBadge::where('user_id', $user->id)
            ->where('AwardDate', '>=', $startDate)
            ->where('AwardDate', '<', $endDate)
            ->whereIn('AwardType', [
                AwardType::Mastery,
                AwardType::GameBeaten,
            ])
            ->join('GameData', 'GameData.ID', '=', 'AwardData')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->get();

        $MASTERED = 1;
        $BEATEN = 2;
        $COMPLETED = 3;
        $BEATENSOFTCORE = 4;

        // determine best award for each game
        $bestAwards = [];
        foreach ($awards as $award) {
            if ($award->AwardDataExtra === 1) {
                $awardType = ($award->AwardType === AwardType::Mastery) ? $MASTERED : $BEATEN;
            } else {
                $awardType = ($award->AwardType === AwardType::Mastery) ? $COMPLETED : $BEATENSOFTCORE;
            }

            if (!array_key_exists($award->AwardData, $bestAwards) || $awardType < $bestAwards[$award->AwardData]) {
                $bestAwards[$award->AwardData] = $awardType;
            }
        }

        // count each type of award
        $counts = [];
        foreach ($bestAwards as $awardType) {
            $counts[$awardType] = ($counts[$awardType] ?? 0) + 1;
        }

        $recapData['numMasteries'] = $counts[$MASTERED] ?? 0;
        $recapData['numBeatenHardcore'] = $counts[$BEATEN] ?? 0;
        $recapData['numCompletions'] = $counts[$COMPLETED] ?? 0;
        $recapData['numBeaten'] = $counts[$BEATENSOFTCORE] ?? 0;
    }

    private function determineMostPlayedGame(array &$recapData, array $gameData): void
    {
        $mostPlayedGame = 0;
        $mostPlayedGameTime = 0;

        foreach ($gameData as $id => $game) {
            if ($game['totalDuration'] > $mostPlayedGameTime) {
                $mostPlayedGameTime = $game['totalDuration'];
                $mostPlayedGame = $id;
            }
        }

        $recapData['mostPlayedGame'] = null;
        $recapData['mostPlayedGamePlaytime'] = '';

        if ($mostPlayedGame) {
            $game = Game::find($mostPlayedGame);
            if ($game) {
                $recapData['mostPlayedGame'] = $game;
                $recapData['mostPlayedGamePlaytime'] = $this->hoursMinutes($mostPlayedGameTime);
            }
        }
    }

    private function determineRarestAchievement(array &$recapData, User $user, Carbon $startDate, Carbon $endDate): void
    {
        $recapData['rarestHardcoreAchievement'] = null;
        $recapData['rarestHardcoreAchievementEarnRate'] = 0.0;
        $recapData['rarestSoftcoreAchievement'] = null;
        $recapData['rarestSoftcoreAchievementEarnRate'] = 0.0;

        $rarestHardcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $startDate)
            ->where('unlocked_hardcore_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->where('Achievements.Flags', AchievementFlag::OfficialCore)
            ->select('Achievements.ID', DB::raw('Achievements.unlocks_hardcore_total/GameData.players_total as EarnRate'))
            ->orderBy('EarnRate')
            ->first();
        if ($rarestHardcoreAchievement) {
            $recapData['rarestHardcoreAchievement'] = Achievement::find($rarestHardcoreAchievement->ID);
            $recapData['rarestHardcoreAchievementEarnRate'] = sprintf("%01.2f", $rarestHardcoreAchievement->EarnRate * 100);

            return; // only report rarest hardcore achievement if one was found
        }

        $rarestSoftcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_at', '>=', $startDate)
            ->where('unlocked_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->where('Achievements.Flags', AchievementFlag::OfficialCore)
            ->select('Achievements.ID', DB::raw('Achievements.unlocks_total/GameData.players_total as EarnRate'))
            ->orderBy('EarnRate')
            ->first();
        if ($rarestSoftcoreAchievement) {
            $recapData['rarestSoftcoreAchievement'] = Achievement::find($rarestSoftcoreAchievement->ID);
            $recapData['rarestSoftcoreAchievementEarnRate'] = sprintf("%01.2f", $rarestSoftcoreAchievement->EarnRate * 100);
        }
    }

    private function summarizePosts(array &$recapData, User $user, Carbon $startDate, Carbon $endDate): void
    {
        $recapData['numForumPosts'] = (!$user->forum_verified_at) ? 0 :
            ForumTopicComment::where('author_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();
        $recapData['numComments'] = Comment::where('user_id', $user->id)
            ->whereIn('ArticleType', [ArticleType::Game, ArticleType::Achievement, ArticleType::Leaderboard])
            ->where('Submitted', '>=', $startDate)
            ->where('Submitted', '<', $endDate)
            ->count();
    }

    private function summarizeDevelopment(array &$recapData, User $user, Carbon $startDate, Carbon $endDate): void
    {
        $recapData['achievementsCreated'] = 0;
        $recapData['completedClaims'] = 0;

        if (!$user->ContribCount) {
            return;
        }

        $recapData['achievementsCreated'] = Achievement::where('user_id', $user->id)
            ->where('Flags', AchievementFlag::OfficialCore)
            ->where('DateCreated', '>=', $startDate)
            ->where('DateCreated', '<', $endDate)
            ->count();

        $recapData['completedClaims'] = AchievementSetClaim::where('user_id', $user->id)
            ->where('SetType', ClaimSetType::NewSet)
            ->where('Status', ClaimStatus::Complete)
            ->where('Finished', '>=', $startDate)
            ->where('Finished', '<', $endDate)
            ->count();
    }

    private function hoursMinutes(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes - $hours * 60;
        if ($hours == 0) {
            return "$minutes minutes";
        }
        if ($minutes == 0 || $hours >= 50) {
            return "$hours hours";
        }

        return "$hours hours and $minutes minutes";
    }
}
