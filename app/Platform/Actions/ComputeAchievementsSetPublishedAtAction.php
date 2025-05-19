<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Comment;
use App\Models\PlayerGame;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class ComputeAchievementsSetPublishedAtAction
{
    public function execute(AchievementSet $achievementSet): ?Carbon
    {
        $achievementIds = $achievementSet->achievements()->where('Flags', AchievementFlag::OfficialCore)->get()->pluck('id')->toArray();

        if (count($achievementIds) === 0) {
            return null;
        }

        // the most reliable way to identify when an achievement set was published
        // is to find the earliest achievement promotion audit log or comment. many
        // sets predate that, so we also need to check for the primary set claim
        // completion timestamp and the first unlocked achievement timestamp.
        $firstPromotionComment = Comment::query()
            ->where('ArticleType', ArticleType::Achievement)
            ->whereIn('ArticleID', $achievementIds)
            ->automated()
            ->whereLike('Payload', '%promoted%')
            ->first();
        $publishedAt = $firstPromotionComment?->Submitted;

        $promotionLogs = Activity::query()
            ->where('subject_type', (new Achievement())->getMorphClass())
            ->whereIn('subject_id', $achievementIds)
            ->whereLike('properties', '%"Flags":3%"Flags":5%') // Flags changed from 5 (unofficial) to 3 (core)
            ->orderBy('created_at')
            ->select('created_at');
        if ($publishedAt) {
            $promotionLogs->where('created_at', '<', $publishedAt);
        }
        $firstPromotionLog = $promotionLogs->first();
        if ($firstPromotionLog) {
            $publishedAt = $firstPromotionLog->created_at;
        }

        // then check the player_games records to find the oldest unlock
        $gameAchievementSet = $achievementSet->gameAchievementSets()->where('type', AchievementSetType::Core)->first();
        if (!$gameAchievementSet) {
            return $publishedAt;
        }
        $game = $gameAchievementSet->game;

        $playerGames = PlayerGame::query()
            ->where('game_id', $game->id)
            ->whereNotNull('first_unlock_at')
            ->orderBy('first_unlock_at');
        if ($publishedAt) {
            $playerGames->where('first_unlock_at', '<', $publishedAt);
        }
        $firstPlayerAchievement = $playerGames->first();
        if ($firstPlayerAchievement) {
            $publishedAt = $firstPlayerAchievement->first_unlock_at;
        }

        // and finally, check for any completed claims
        $claims = $game->achievementSetClaims()
            ->newSet()
            ->primaryClaim()
            ->complete()
            ->whereNotNull('Finished')
            ->orderBy('Finished');
        if ($publishedAt) {
            $claims->where('Finished', '<', $publishedAt);
        }
        $firstClaim = $claims->first();
        if ($firstClaim) {
            $publishedAt = $firstClaim->Finished;
        }

        return $publishedAt;
    }
}
