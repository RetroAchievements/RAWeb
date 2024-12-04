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
        $january = Carbon::create($year, 1, 1, 0, 0, 0);

        $numAchievements = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where(function ($query) use ($january) {
                $query->where('unlocked_at', '>=', $january)
                      ->orWhere('unlocked_hardcore_at', '>=', $january);
            })
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->count();

        $numLeaderboards = LeaderboardEntry::where('user_id', $user->id)
            ->where('updated_at', '>=', $january)
            ->count();

        $hardcorePoints = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $january)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->sum('Achievements.Points');
        $softcorePoints = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->whereNull('unlocked_hardcore_at')
            ->where('unlocked_at', '>=', $january)
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->sum('Achievements.Points');

        $games = PlayerSession::where('user_id', $user->id)
            ->where('duration', '>=', 5)
            ->where('created_at', '>=', $january)
            ->join('GameData', 'GameData.ID', '=', 'game_id')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems());
        $numGames = $games->clone()->distinct('game_id')->count();

        $body = "<p>Congratulations {$user->display_name}!\n";
        $body .= "<p>In $year, you've played $numGames games on " .
                 "<a href=\"" . route('home') . "\">retroachievements.org</a>" .
                 " and unlocked $numAchievements achievements, earning you ";

        if ($hardcorePoints > 0) {
            $body .= "$hardcorePoints hardcore points";
        }
        if ($softcorePoints > 0) {
            if ($hardcorePoints > 0) {
                $body .= " and ";
            }
            $body .= "$softcorePoints softcore points";
        }
        $body .= '.';

        if ($numLeaderboards > 0) {
            $body .= " You submitted new scores for $numLeaderboards leaderboards.";
        }

        $body .= "\n";

        $totalTime = (int) $games->clone()->sum('duration');
        $body .= "<p>You spent " . $this->hoursMinutes($totalTime) . " playing games ";

        $numSystems = $games->clone()->distinct('ConsoleID')->count();
        if ($numSystems === 1) {
            $body .= "on 1 system.";
        } else {
            $body .= "across $numSystems systems.";

            $mostPlayedSystem = $games->clone()
                ->groupBy('ConsoleID')
                ->select('ConsoleID', DB::raw('sum(duration) as totalDuration'))
                ->orderByDesc('totalDuration')
                ->first();

            $system = System::find($mostPlayedSystem->ConsoleID);
            if ($system) {
                $body .= ' ' . $this->hoursMinutes((int) $mostPlayedSystem->totalDuration) .
                    " of that were playing {$system->Name} games.";
            }
        }
        $body .= "\n";

        $numMasteries = PlayerBadge::where('user_id', $user->id)
            ->where('AwardDate', '>=', $january)
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardDataExtra', 1)
            ->count();
        $numBeatenHardcore = PlayerBadge::where('user_id', $user->id)
            ->where('AwardDate', '>=', $january)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardDataExtra', 1)
            ->count();
        $numCompletions = PlayerBadge::where('user_id', $user->id)
            ->where('AwardDate', '>=', $january)
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardDataExtra', 0)
            ->count();
        $numBeaten = PlayerBadge::where('user_id', $user->id)
            ->where('AwardDate', '>=', $january)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardDataExtra', 0)
            ->count();

        if ($numMasteries > 0 || $numBeatenHardcore > 0 || $numCompletions > 0 || $numBeaten > 0) {
            $body .= '<p>';
            if ($numMasteries > 0) {
                if ($numBeatenHardcore > 0) {
                    $body .= "You mastered $numMasteries games, and beat $numBeatenHardcore games on hardcore. ";
                } else {
                    $body .= "You mastered $numMasteries games. ";
                }
            } elseif ($numBeatenHardcore > 0) {
                $body .= "You beat $numBeatenHardcore games on hardcore. ";
            }
            if ($numCompletions > 0) {
                if ($numBeaten > 0) {
                    $body .= "You completed $numCompletions games, and beat $numBeaten games on softcore.";
                } else {
                    $body .= "You completed $numCompletions games.";
                }
            } elseif ($numBeaten > 0) {
                $body .= "You beat $numBeaten games on softcore.";
            }
            $body .= "\n";
        }

        $mostPlayedGame = $games->clone()->groupBy('game_id')
            ->select('game_id', DB::raw('sum(duration) as totalDuration'))
            ->orderByDesc('totalDuration')
            ->first();
        if ($mostPlayedGame) {
            $game = Game::find($mostPlayedGame->game_id);
            if ($game) {
                $body .= "<p>Your most played game was <a href=\"" .
                    route('game.show', $game) . "\">{$game->Title}</a> at " .
                    $this->hoursMinutes((int) $mostPlayedGame->totalDuration) . ".\n";
            }
        }

        $rarestHardcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
            ->where('unlocked_hardcore_at', '>=', $january)
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
                $body .= "<p>Your rarest achievement earned was " .
                    "<a href=\"" . route('achievement.show', $achievement) . "\">{$achievement->Title}</a>" .
                    " from {$achievement->game->Title}, which has only been earned in hardcore by " .
                    sprintf("%01.2f", $rarestHardcoreAchievement->EarnRate * 100) . "% of players.\n";
            }
        } else {
            $rarestSoftcoreAchievement = PlayerAchievement::where('player_achievements.user_id', $user->id)
                ->where('unlocked_at', '>=', $january)
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
                    $body .= "<p>Your rarest achievement earned was " .
                        "<a href=\"" . route('achievement.show', $achievement) . "\">{$achievement->Title}</a>" .
                        " from {$achievement->game->Title}, which has only been earned by " .
                        sprintf("%01.2f", $rarestSoftcoreAchievement->EarnRate * 100) . "% of players.\n";
                }
            }
        }

        $numForumPosts = (!$user->forum_verified_at) ? 0 :
            ForumTopicComment::where('author_id', $user->id)
            ->where('DateCreated', '>=', $january)
            ->count();
        $numComments = Comment::where('user_id', $user->id)
            ->whereIn('ArticleType', [ArticleType::Game, ArticleType::Achievement, ArticleType::Leaderboard])
            ->where('Submitted', '>=', $january)
            ->count();

        if ($numForumPosts > 0) {
            if ($numComments > 0) {
                $body .= "<p>You made $numForumPosts forum posts and $numComments game comments.\n";
            } else {
                $body .= "<p>You made $numForumPosts forum posts.\n";
            }
        } elseif ($numComments > 0) {
            $body .= "<p>You made $numComments game comments.\n";
        }

        if ($user->ContribCount > 0) {
            $numAchievementsCreated = Achievement::where('user_id', $user->id)
                ->where('Flags', AchievementFlag::OfficialCore)
                ->where('DateCreated', '>=', $january)
                ->count();

            $numCompletedClaims = AchievementSetClaim::where('user_id', $user->id)
                ->where('SetType', ClaimSetType::NewSet)
                ->where('Status', ClaimStatus::Complete)
                ->where('Finished', '>=', $january)
                ->count();

            if ($numAchievementsCreated > 0) {
                if ($numCompletedClaims > 0) {
                    $body .= "<p>You published $numAchievementsCreated new achievements and $numCompletedClaims new sets.\n";
                } else {
                    $body .= "<p>You published $numAchievementsCreated new achievements.\n";
                }
            } elseif ($numCompletedClaims > 0) {
                // this should never happen as new sets should have new achievements
            }
        }

        // (new \Symfony\Component\Console\Output\ConsoleOutput())->writeln($body);

        $subject = "RetroAchievements $year Year in Review for {$user->display_name}";
        mail_utf8($user->EmailAddress, $subject, $body);
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
