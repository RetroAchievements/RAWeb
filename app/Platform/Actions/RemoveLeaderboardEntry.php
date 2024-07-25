<?php

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\LeaderboardEntry;
use App\Platform\Enums\ValueFormat;
use Illuminate\Support\Facades\Auth;

class RemoveLeaderboardEntry
{
    public function execute(LeaderboardEntry $entry, ?string $reason): void
    {
        $currentUser = Auth::user();

        // Authorize.
        if (!$currentUser->can('delete', $entry)) {
            return;
        }

        $entry->loadMissing(['leaderboard', 'user']);
        $score = ValueFormat::format($entry->score, $entry->leaderboard->format);
        $targetUser = $entry->user;

        $wasRemoved = $entry->delete();
        if ($wasRemoved && !$targetUser->is($currentUser)) {
            $commentText = "removed {$targetUser->display_name}'s entry of {$score} from this leaderboard";
            if ($reason) {
                $commentText .= ". Reason: {$reason}";
            }

            addArticleComment(
                "Server",
                ArticleType::Leaderboard,
                $entry->leaderboard->id,
                "{$currentUser->display_name} {$commentText}",
                $currentUser->username
            );
        }
    }
}
