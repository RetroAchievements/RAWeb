<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Events\AchievementPromoted;
use App\Platform\Events\LeaderboardPromoted;
use RuntimeException;
use Spatie\Activitylog\CauserResolver;

class EnsureTriggerVersionedOnPromotion
{
    public function handle(AchievementPromoted|LeaderboardPromoted $event): void
    {
        $model = null;
        $conditions = null;

        if ($event instanceof AchievementPromoted) {
            $model = $event->achievement;
            $conditions = $model->trigger_definition;
        } else {
            $model = $event->leaderboard;
            $conditions = $model->trigger_definition;
        }

        $model->loadMissing('trigger');
        if (!$model->trigger) {
            return;
        }

        if ($model->trigger->version !== null) {
            return;
        }

        $user = app(CauserResolver::class)->resolve();
        if (!$user instanceof User) {
            throw new RuntimeException('Cannot version trigger: no authenticated user.');
        }

        (new UpsertTriggerVersionAction())->execute(
            $model,
            $conditions,
            versioned: true,
            user: $user
        );
    }
}
