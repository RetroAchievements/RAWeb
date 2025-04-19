<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\PlayerGame;
use Carbon\Carbon;

class ComputeGameAchievementsPublishedAtAction
{
    public function execute(Game $game): ?Carbon
    {
        $achievementIds = $game->achievements()->published()->get()->pluck('id')->toArray();

        if (count($achievementIds) === 0) {
            return null;
        }

        // the most reliable way to identify when an achievement set was published
        // is to find the earliest achievement promotion audit comment. many
        // sets predate that, so we also need to check for the primary set claim
        // completion timestamp and the first unlocked achievement timestamp.
        $firstPromotionComment = Comment::where('ArticleType', ArticleType::Achievement)
            ->whereIn('ArticleID', $achievementIds)
            ->automated()
            ->whereLike('Payload', '%promoted%')
            ->first();
        $publishedAt = $firstPromotionComment?->Submitted;

        $playerGames = PlayerGame::where('game_id', $game->id)
            ->whereNotNull('first_unlock_at')
            ->orderBy('first_unlock_at');
        if ($publishedAt) {
            $playerGames->where('first_unlock_at', '<', $publishedAt);
        }
        $firstPlayerAchievement = $playerGames->first();
        if ($firstPlayerAchievement) {
            $publishedAt = $firstPlayerAchievement->first_unlock_at;
        }

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
