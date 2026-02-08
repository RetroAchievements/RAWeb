<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Events\AchievementPromoted;
use RuntimeException;
use Spatie\Activitylog\CauserResolver;

class EnsureTriggerVersionedOnPromotion
{
    public function handle(AchievementPromoted $event): void
    {
        $achievement = $event->achievement;

        $achievement->loadMissing('trigger');
        if (!$achievement->trigger) {
            return;
        }

        if ($achievement->trigger->version !== null) {
            return;
        }

        $user = app(CauserResolver::class)->resolve();
        if (!$user instanceof User) {
            throw new RuntimeException('Cannot version trigger: no authenticated user.');
        }

        (new UpsertTriggerVersionAction())->execute(
            $achievement,
            $achievement->trigger->conditions,
            versioned: true,
            user: $user
        );
    }
}
