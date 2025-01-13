<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
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

        $subject = "RetroAchievements $year Year in Review for {$user->display_name}";

        $body = "<p>Congratulations {$user->display_name}!\n";
        $body .= $this->generateSummary($user, $gameData, $startDate, $endDate);
        $body .= $this->summarizePlayTime($gameData);
        $body .= $this->summarizeAwards($user, $startDate, $endDate);
        $body .= $this->mostPlayedGame($gameData);
        $body .= $this->rarestAchievement($user, $startDate, $endDate);
        $body .= $this->summarizePosts($user, $startDate, $endDate);
        $body .= $this->summarizeDevelopment($user, $startDate, $endDate);

        $body .= "\n";

        // (new \Symfony\Component\Console\Output\ConsoleOutput())->writeln($body);
        mail_utf8($user->EmailAddress, $subject, $body);
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
                'totalDuration' => $game->totalDuration,
            ];
        }

        return $gameData;
    }

    private function generateSummary(User $user, array $gameData, Carbon $startDate, Carbon $endDate): string
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

        $numGames = count($gameData);

        $message = "<p>In {$startDate->year}, you played $numGames games on " .
                   "<a href=\"" . route('home') . "\">retroachievements.org</a>";

        $numAchievements = (int) ($hardcoreTally->count + $softcoreTally->count);
        if ($numAchievements > 0) {
            $message .= " and unlocked $numAchievements achievements, earning you ";

            if ($hardcoreTally->points > 0) {
                $message .= "{$hardcoreTally->points} hardcore points";
            }
            if ($softcoreTally->points > 0) {
                if ($hardcoreTally->points > 0) {
                    $message .= " and ";
                }
                $message .= "{$softcoreTally->points} softcore points";
            }
        }

        $message .= '.';

        if ($numLeaderboards > 0) {
            $message .= " You submitted new scores for $numLeaderboards leaderboards.";
        }

        $message .= "\n";

        return $message;
    }

    private function summarizePlayTime(array $gameData): string
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

        $message = "<p>You spent " . $this->hoursMinutes($totalTime) . " playing games ";

        $numSystems = count($systemTimes);
        if ($numSystems === 1) {
            $message .= "on 1 system.";
        } else {
            $message .= "across $numSystems systems.";

            $system = System::find($mostPlayedSystem);
            if ($system) {
                $message .= ' ' . $this->hoursMinutes($systemTimes[$mostPlayedSystem]) .
                            " of that were playing {$system->Name} games.";
            }
        }
        $message .= "\n";

        return $message;
    }

    private function summarizeAwards(User $user, Carbon $startDate, Carbon $endDate): string
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

        if (empty($counts)) {
            return "";
        }

        $numMasteries = $counts[$MASTERED] ?? 0;
        $numBeatenHardcore = $counts[$BEATEN] ?? 0;
        $numCompletions = $counts[$COMPLETED] ?? 0;
        $numBeaten = $counts[$BEATENSOFTCORE] ?? 0;

        $message = '<p>';
        if ($numMasteries > 0) {
            if ($numBeatenHardcore > 0) {
                $message .= "You mastered $numMasteries games, and beat $numBeatenHardcore games on hardcore. ";
            } else {
                $message .= "You mastered $numMasteries games. ";
            }
        } elseif ($numBeatenHardcore > 0) {
            $message .= "You beat $numBeatenHardcore games on hardcore. ";
        }
        if ($numCompletions > 0) {
            if ($numBeaten > 0) {
                $message .= "You completed $numCompletions games, and beat $numBeaten games on softcore.";
            } else {
                $message .= "You completed $numCompletions games.";
            }
        } elseif ($numBeaten > 0) {
            $message .= "You beat $numBeaten games on softcore.";
        }
        $message .= "\n";

        return $message;
    }

    private function mostPlayedGame(array $gameData): string
    {
        $mostPlayedGame = 0;
        $mostPlayedGameTime = 0;

        foreach ($gameData as $id => $game) {
            if ($game['totalDuration'] > $mostPlayedGameTime) {
                $mostPlayedGameTime = $game['totalDuration'];
                $mostPlayedGame = $id;
            }
        }

        if ($mostPlayedGame) {
            $game = Game::find($mostPlayedGame);
            if ($game) {
                return "<p>Your most played game was <a href=\"" .
                    route('game.show', $game) . "\">{$game->Title}</a> at " .
                    $this->hoursMinutes((int) $mostPlayedGameTime) . ".\n";
            }
        }

        return "";
    }

    private function rarestAchievement(User $user, Carbon $startDate, Carbon $endDate): string
    {
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
            $achievement = Achievement::find($rarestHardcoreAchievement->ID);
            if ($achievement) {
                return "<p>Your rarest achievement earned was " .
                    "<a href=\"" . route('achievement.show', $achievement) . "\">{$achievement->Title}</a>" .
                    " from {$achievement->game->Title}, which has only been earned in hardcore by " .
                    sprintf("%01.2f", $rarestHardcoreAchievement->EarnRate * 100) . "% of players.\n";
            }
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
            $achievement = Achievement::find($rarestSoftcoreAchievement->ID);
            if ($achievement) {
                return "<p>Your rarest achievement earned was " .
                    "<a href=\"" . route('achievement.show', $achievement) . "\">{$achievement->Title}</a>" .
                    " from {$achievement->game->Title}, which has only been earned by " .
                    sprintf("%01.2f", $rarestSoftcoreAchievement->EarnRate * 100) . "% of players.\n";
            }
        }

        return "";
    }

    private function summarizePosts(User $user, Carbon $startDate, Carbon $endDate): string
    {
        $numForumPosts = (!$user->forum_verified_at) ? 0 :
            ForumTopicComment::where('author_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();
        $numComments = Comment::where('user_id', $user->id)
            ->whereIn('ArticleType', [ArticleType::Game, ArticleType::Achievement, ArticleType::Leaderboard])
            ->where('Submitted', '>=', $startDate)
            ->where('Submitted', '<', $endDate)
            ->count();

        $message = '';
        if ($numForumPosts > 0) {
            if ($numComments > 0) {
                $message = "<p>You made $numForumPosts forum posts and $numComments game comments.\n";
            } else {
                $message = "<p>You made $numForumPosts forum posts.\n";
            }
        } elseif ($numComments > 0) {
            $message = "<p>You made $numComments game comments.\n";
        }

        return $message;
    }

    private function summarizeDevelopment(User $user, Carbon $startDate, Carbon $endDate): string
    {
        if (!$user->ContribCount) {
            return "";
        }

        $numAchievementsCreated = Achievement::where('user_id', $user->id)
            ->where('Flags', AchievementFlag::OfficialCore)
            ->where('DateCreated', '>=', $startDate)
            ->where('DateCreated', '<', $endDate)
            ->count();

        $numCompletedClaims = AchievementSetClaim::where('user_id', $user->id)
            ->where('SetType', ClaimSetType::NewSet)
            ->where('Status', ClaimStatus::Complete)
            ->where('Finished', '>=', $startDate)
            ->where('Finished', '<', $endDate)
            ->count();

        $message = '';
        if ($numAchievementsCreated > 0) {
            if ($numCompletedClaims > 0) {
                $message = "<p>You published $numAchievementsCreated new achievements and $numCompletedClaims new sets.\n";
            } else {
                $message = "<p>You published $numAchievementsCreated new achievements.\n";
            }
        } elseif ($numCompletedClaims > 0) {
            // this should never happen as new sets should have new achievements
        }

        return $message;
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
