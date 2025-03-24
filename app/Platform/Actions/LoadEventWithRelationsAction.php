<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Event;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;

class LoadEventWithRelationsAction
{
    /**
     * Efficiently load an event for the event page with all its required relations.
     *
     * @param Event $event the event to load relations for
     * @param ?User $user the current authenticated user, if any
     * @return Event the event with properly loaded relations
     */
    public function execute(Event $event, ?User $user): Event
    {
        $event->loadMissing([
            'legacyGame',
            'achievements' => function ($query) use ($user) {
                $query->with(['sourceAchievement.game.system'])
                    ->where('Flags', AchievementFlag::OfficialCore->value);

                if ($user) {
                    $query->with(['achievement.playerAchievements' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }]);
                }
            },
            'awards' => function ($query) use ($user, $event) {
                $query->withCount('playerBadges as badge_count');

                if ($user) {
                    $query->with(['playerBadges' => function ($query) use ($user, $event) {
                        $query
                            ->where('user_id', $user->id)
                            ->where('AwardData', $event->id);
                    }]);
                }
            },
        ]);

        return $event;
    }
}
