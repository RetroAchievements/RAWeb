<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Mail\AnnualRecapMail;
use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Event;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GenerateAnnualRecapAction
{
    public function execute(User $user): void
    {
        // user must have a verified email address, and not be banned.
        if (!$user->EmailAddress || !$user->isEmailVerified() || $user->isBanned()) {
            return;
        }

        $year = Carbon::now()->subMonths(6)->year;
        $startDate = Carbon::create($year, 1, 1, 0, 0, 0);
        $endDate = Carbon::create($year + 1, 1, 1, 0, 0, 0);

        $gameData = $this->getGameData($user, $startDate, $endDate);

        // don't bother generating recap if the player has less than 10 hours of playtime for the year
        // or has only played standalone games.
        $totalDuration = 0;
        $hasPlayedNonStandaloneGame = false;
        foreach ($gameData as $game) {
            $totalDuration += $game['totalDuration'];

            $hasPlayedNonStandaloneGame |= ($game['ConsoleID'] !== System::Standalones);
        }

        if ($totalDuration < 600 || !$hasPlayedNonStandaloneGame) {
            return;
        }

        // build recap data
        $recapData = [
            'year' => $year,
        ];

        $this->extractDevelopmentTime($recapData, $user, $gameData, $startDate, $endDate);

        $subsetGameIds = $this->identifyAndMergeSubSets($gameData);

        $this->summarizeUnlocks($recapData, $user, $gameData, $startDate, $endDate);
        $this->summarizePlayTime($recapData, $gameData);
        $this->summarizeAwards($recapData, $user, $startDate, $endDate);
        $this->determineMostPlayedGame($recapData, $gameData);
        $this->determineRarestAchievement($recapData, $user, $gameData, $startDate, $endDate);
        $this->determineRarestSubsetAchievement($recapData, $user, $subsetGameIds, $startDate, $endDate);
        $this->summarizePosts($recapData, $user, $startDate, $endDate);
        $this->summarizeDevelopment($recapData, $user, $startDate, $endDate);

        // send email
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

    private function extractDevelopmentTime(array &$recapData, User $user, array &$gameData, Carbon $startDate, Carbon $endDate): void
    {
        $gameIds = AchievementSetClaim::where('user_id', $user->id)
            ->where('Finished', '>', $startDate)
            ->select('game_id')
            ->get()
            ->pluck('game_id')
            ->unique()
            ->toArray();

        if (empty($gameIds)) {
            $recapData['developmentTime'] = null;

            return;
        }

        $achievementSetIds = GameAchievementSet::whereIn('game_id', $gameIds)
            ->select('game_id', 'achievement_set_id')
            ->where('type', AchievementSetType::Core)
            ->get()
            ->mapWithKeys(function ($gameAchievementSet) {
                return [$gameAchievementSet->game_id => $gameAchievementSet->achievement_set_id];
            });

        $achievementSetPublishedDates = AchievementSet::whereIn('id', $achievementSetIds)
            ->select(['id', 'achievements_first_published_at'])
            ->get()
            ->mapWithKeys(function ($achievementSet) {
                return [$achievementSet->id => $achievementSet->achievements_first_published_at];
            });

        $developmentTime = 0;
        foreach ($gameIds as $gameId) {
            if (!array_key_exists($gameId, $gameData)) {
                // user has a claim on a game they haven't played.
                continue;
            }

            $achievementsPublishedDate = $achievementSetPublishedDates[$achievementSetIds[$gameId] ?? 0] ?? null;
            if (!$achievementsPublishedDate) {
                // if achievements haven't been published, assume all time is development time
                $gameDevelopmentTime = $gameData[$gameId]['totalDuration'];
                $developmentTime += $gameDevelopmentTime;
                $gameData[$gameId]['developmentTime'] = $gameDevelopmentTime;
                $gameData[$gameId]['totalDuration'] = 0;
                continue;
            }

            // assume all time before achievements were published is development time
            $sessions = PlayerSession::where('user_id', $user->id)
                ->where('game_id', $gameId)
                ->where('duration', '>=', 5)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<', $achievementsPublishedDate)
                ->get();

            $gameDevelopmentTime = 0;
            foreach ($sessions as $session) {
                if ($session->updated_at > $achievementsPublishedDate) {
                    $sessionTime = (int) $session->updated_at->diffInMinutes($session->created_at, true);
                } else {
                    $sessionTime = $session->duration;
                }
                $gameDevelopmentTime += $sessionTime;
            }

            $developmentTime += $gameDevelopmentTime;
            $gameData[$gameId]['totalDuration'] =
                max($gameData[$gameId]['totalDuration'] - $gameDevelopmentTime, 0);
            $gameData[$gameId]['developmentTime'] =
                ($gameData[$gameId]['developmentTime'] ?? 0) + $gameDevelopmentTime;
        }

        $recapData['developmentTime'] = $developmentTime > 0 ? $this->hoursMinutes($developmentTime) : null;
    }

    private function identifyAndMergeSubsets(array &$gameData): array
    {
        $gameIds = array_keys($gameData);
        $achievementSets = GameAchievementSet::whereIn('game_id', $gameIds)
            ->select(['game_id', 'achievement_set_id'])
            ->where('type', AchievementSetType::Core)
            ->get()
            ->mapWithKeys(function ($gameAchievementSet) {
                return [$gameAchievementSet->achievement_set_id => $gameAchievementSet->game_id];
            })
            ->toArray();

        $subsets = GameAchievementSet::whereIn('achievement_set_id', array_keys($achievementSets))
            ->select(['game_id', 'achievement_set_id'])
            ->where('type', '!=', AchievementSetType::Core)
            ->get()
            ->mapWithKeys(function ($gameAchievementSet) {
                return [$gameAchievementSet->achievement_set_id => $gameAchievementSet->game_id];
            })
            ->toArray();

        $subsetGameIds = [];
        foreach ($subsets as $setId => $gameId) {
            $subsetGameId = $achievementSets[$setId] ?? 0;
            if ($subsetGameId) {
                $subsetGameIds[] = $subsetGameId;

                // move the playtime from the subset to the core set
                if (array_key_exists($subsetGameId, $gameData)) {
                    if (array_key_exists($gameId, $gameData)) {
                        $gameData[$gameId]['totalDuration'] += $gameData[$subsetGameId]['totalDuration'];
                    } else {
                        $gameData[$gameId] = $gameData[$subsetGameId];
                    }

                    unset($gameData[$subsetGameId]);
                }
            }
        }

        return $subsetGameIds;
    }

    private function summarizeUnlocks(array &$recapData, User $user, array $gameData, Carbon $startDate, Carbon $endDate): void
    {
        $gameIds = array_keys($gameData);

        $unlockTallies = $this->getUnlockTallies($gameIds, $user, $startDate, $endDate);

        $numLeaderboards = LeaderboardEntry::where('user_id', $user->id)
            ->where('updated_at', '>=', $startDate)
            ->where('updated_at', '<', $endDate)
            ->count();

        $recapData['gamesPlayed'] = count($gameData);
        $recapData['achievementsUnlocked'] = $unlockTallies['achievementsUnlocked'];
        $recapData['hardcorePointsEarned'] = $unlockTallies['hardcorePointsEarned'];
        $recapData['softcorePointsEarned'] = $unlockTallies['softcorePointsEarned'];
        $recapData['leaderboardsSubmitted'] = $numLeaderboards;
    }

    private function getUnlockTallies(array $gameIds, User $user, Carbon $startDate, Carbon $endDate): array
    {
        $hardcoreTally = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $startDate)
            ->where('unlocked_hardcore_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->whereIn(DB::raw('Achievements.GameID'), $gameIds)
            ->where(DB::raw('Achievements.Flags'), AchievementFlag::OfficialCore)
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
            ->whereIn(DB::raw('Achievements.GameID'), $gameIds)
            ->where(DB::raw('Achievements.Flags'), AchievementFlag::OfficialCore)
            ->select(
                DB::raw('count(*) as count'),
                DB::raw('sum(Achievements.Points) as points'),
            )
            ->first();

        return [
            'achievementsUnlocked' => $hardcoreTally->count + $softcoreTally->count,
            'hardcorePointsEarned' => $hardcoreTally->points,
            'softcorePointsEarned' => $softcoreTally->points,
        ];
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
            $recapData['mostPlayedSystem'] = $system->name;
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
                AwardType::Event,
                AwardType::AchievementUnlocksYield,
                AwardType::AchievementPointsYield,
                AwardType::CertifiedLegend,
            ])
            ->join('GameData', 'GameData.ID', '=', 'AwardData')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->get();

        $recapData['numEventAwards'] = 0;
        $recapData['numSiteAwards'] = 0;
        $eventIds = [];

        $OTHER = 0;
        $MASTERED = 1;
        $BEATEN = 2;
        $COMPLETED = 3;
        $BEATENSOFTCORE = 4;

        // determine best award for each game
        $bestAwards = [];
        foreach ($awards as $award) {
            switch ($award->AwardType) {
                case AwardType::Mastery:
                    $awardType = ($award->AwardDataExtra === 1) ? $MASTERED : $COMPLETED;
                    break;

                case AwardType::GameBeaten:
                    $awardType = ($award->AwardDataExtra === 1) ? $BEATEN : $BEATENSOFTCORE;
                    break;

                case AwardType::Event:
                    $eventIds[] = $award->AwardData;
                    $awardType = $OTHER;
                    break;

                default: // AchievementUnlocks, AchievementYields, CertifiedLegend
                    $recapData['numSiteAwards']++;
                    $awardType = $OTHER;
                    break;
            }

            if ($awardType !== $OTHER) {
                if (!array_key_exists($award->AwardData, $bestAwards) || $awardType < $bestAwards[$award->AwardData]) {
                    $bestAwards[$award->AwardData] = $awardType;
                }
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

        if (!empty($eventIds)) {
            foreach (Event::whereIn('ID', $eventIds)->get() as $event) {
                if ($event->gives_site_award) {
                    $recapData['numSiteAwards']++;
                } else {
                    $recapData['numEventAwards']++;
                }
            }
        }
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

    private function determineRarestAchievement(array &$recapData, User $user, array $gameData, Carbon $startDate, Carbon $endDate): void
    {
        $gameIds = array_keys($gameData);
        $rarestAchievement = $this->getRarestAchievement($gameIds, $user, $startDate, $endDate);
        $recapData['rarestHardcoreAchievement'] = $rarestAchievement['rarestHardcoreAchievement'];
        $recapData['rarestHardcoreAchievementEarnRate'] = $rarestAchievement['rarestHardcoreAchievementEarnRate'];
        $recapData['rarestSoftcoreAchievement'] = $rarestAchievement['rarestSoftcoreAchievement'];
        $recapData['rarestSoftcoreAchievementEarnRate'] = $rarestAchievement['rarestSoftcoreAchievementEarnRate'];
    }

    private function getRarestAchievement(array $gameIds, User $user, Carbon $startDate, Carbon $endDate): array
    {
        $result = [
            'rarestHardcoreAchievement' => null,
            'rarestHardcoreAchievementEarnRate' => 0.0,
            'rarestSoftcoreAchievement' => null,
            'rarestSoftcoreAchievementEarnRate' => 0.0,
        ];

        $rarestHardcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $startDate)
            ->where('unlocked_hardcore_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereIn('Achievements.GameID', $gameIds)
            ->where(DB::raw('Achievements.Flags'), AchievementFlag::OfficialCore)
            ->select('Achievements.ID', DB::raw('Achievements.unlocks_hardcore_total/GameData.players_total as EarnRate'))
            ->orderBy('EarnRate')
            ->first();
        if ($rarestHardcoreAchievement) {
            $result['rarestHardcoreAchievement'] = Achievement::find($rarestHardcoreAchievement->ID);
            $result['rarestHardcoreAchievementEarnRate'] = sprintf("%01.2f", $rarestHardcoreAchievement->EarnRate * 100);

            return $result; // only report rarest hardcore achievement if one was found
        }

        $rarestSoftcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_at', '>=', $startDate)
            ->where('unlocked_at', '<', $endDate)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereIn('Achievements.GameID', $gameIds)
            ->where(DB::raw('Achievements.Flags'), AchievementFlag::OfficialCore)
            ->select('Achievements.ID', DB::raw('Achievements.unlocks_total/GameData.players_total as EarnRate'))
            ->orderBy('EarnRate')
            ->first();
        if ($rarestSoftcoreAchievement) {
            $result['rarestSoftcoreAchievement'] = Achievement::find($rarestSoftcoreAchievement->ID);
            $result['rarestSoftcoreAchievementEarnRate'] = sprintf("%01.2f", $rarestSoftcoreAchievement->EarnRate * 100);
        }

        return $result;
    }

    private function determineRarestSubsetAchievement(array &$recapData, User $user, array $subsetGameIds, Carbon $startDate, Carbon $endDate): void
    {
        if (empty($subsetGameIds)) {
            $recapData['subsetAchievementsUnlocked'] = 0;
            $recapData['subsetHardcorePointsEarned'] = 0;
            $recapData['subsetSoftcorePointsEarned'] = 0;
            $recapData['rarestSubsetHardcoreAchievement'] = null;
            $recapData['rarestSubsetHardcoreAchievementEarnRate'] = 0.0;
            $recapData['rarestSubsetSoftcoreAchievement'] = null;
            $recapData['rarestSubsetSoftcoreAchievementEarnRate'] = 0.0;

            return;
        }

        $subsetUnlockTallies = $this->getUnlockTallies($subsetGameIds, $user, $startDate, $endDate);
        $recapData['subsetAchievementsUnlocked'] = $subsetUnlockTallies['achievementsUnlocked'];
        $recapData['subsetHardcorePointsEarned'] = $subsetUnlockTallies['hardcorePointsEarned'];
        $recapData['subsetSoftcorePointsEarned'] = $subsetUnlockTallies['softcorePointsEarned'];

        $rarestAchievement = $this->getRarestAchievement($subsetGameIds, $user, $startDate, $endDate);
        $recapData['rarestSubsetHardcoreAchievement'] = $rarestAchievement['rarestHardcoreAchievement'];
        $recapData['rarestSubsetHardcoreAchievementEarnRate'] = $rarestAchievement['rarestHardcoreAchievementEarnRate'];
        $recapData['rarestSubsetSoftcoreAchievement'] = $rarestAchievement['rarestSoftcoreAchievement'];
        $recapData['rarestSubsetSoftcoreAchievementEarnRate'] = $rarestAchievement['rarestSoftcoreAchievementEarnRate'];
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
