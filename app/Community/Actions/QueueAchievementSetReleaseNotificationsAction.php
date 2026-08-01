<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\UserDelayedSubscription;
use App\Models\UserGameListEntry;
use Illuminate\Database\Eloquent\Builder;

class QueueAchievementSetReleaseNotificationsAction
{
    /**
     * Queues a daily digest notice for every player waiting on this game via their Want to Play list.
     *
     * Players who spent a set request on the game are deliberately excluded.
     * They receive an immediate email instead.
     */
    public function execute(Game $game): void
    {
        $requesterIds = UserGameListEntry::query()
            ->where('game_id', $game->id)
            ->where('type', UserGameListType::AchievementSetRequest)
            ->pluck('user_id');

        $candidateIds = UserGameListEntry::query()
            ->where('game_id', $game->id)
            ->where('type', UserGameListType::Play)
            ->whereNotIn('user_id', $requesterIds)
            ->whereHas('user', fn (Builder $query) => $query->whereNull('banned_at'))
            ->pluck('user_id');

        if ($candidateIds->isEmpty()) {
            return;
        }

        $alreadyQueuedIds = UserDelayedSubscription::query()
            ->whereIn('user_id', $candidateIds)
            ->where('subject_type', SubscriptionSubjectType::AchievementSetRelease)
            ->where('subject_id', $game->id)
            ->pluck('user_id');

        $newUserIds = $candidateIds->diff($alreadyQueuedIds);
        if ($newUserIds->isEmpty()) {
            return;
        }

        UserDelayedSubscription::insert(
            $newUserIds->map(fn (int $userId) => [
                'user_id' => $userId,
                'subject_type' => SubscriptionSubjectType::AchievementSetRelease->value,
                'subject_id' => $game->id,
                'first_update_id' => $game->id,
            ])->all()
        );
    }
}
