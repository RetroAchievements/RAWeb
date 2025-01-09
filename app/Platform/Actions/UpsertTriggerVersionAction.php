<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Contracts\HasVersionedTrigger;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class UpsertTriggerVersionAction
{
    public function execute(
        Model $triggerable,
        string $conditions,
        bool $versioned = true,
        ?User $user = null,
    ): ?Trigger {
        if (!$triggerable instanceof HasVersionedTrigger) {
            return null;
        }

        $triggerable->loadMissing('trigger');

        // Check if this triggerable has version history (for maintaining versions when demoted).
        $hasVersionHistory = $triggerable->triggers()->whereNotNull('version')->exists();
        $shouldKeepVersion = $versioned || $hasVersionHistory;

        // For unversioned triggers, explicitly check if one already exists.
        // MySQL/MariaDB ignore uniqueness constraints on NULL column values (version),
        // so if we're not careful, we can wind up with lots of unversioned triggers
        // for the same triggerable asset (achievement).
        if (!$shouldKeepVersion) {
            $currentTrigger = $triggerable->triggers()->unversioned()->first();

            if (!$currentTrigger && $triggerable->triggers()->unversioned()->exists()) {
                throw new RuntimeException('Multiple unversioned triggers detected for ' . get_class($triggerable) . ' #' . $triggerable->getKey());
            }

            // If there's already an unversioned trigger for this asset, just update it in-place.
            if ($currentTrigger) {
                $currentTrigger->update([
                    'conditions' => $conditions,
                    'user_id' => $user->id,
                ]);
                $triggerable->update(['trigger_id' => $currentTrigger->id]);

                return $currentTrigger;
            }

            // Otherwise, create a new unversioned trigger. It'll get its first version on publish.
            $trigger = $triggerable->trigger()->save(new Trigger([
                'conditions' => $conditions,
                'version' => null,
                'user_id' => $user->id,
            ]));
            $triggerable->update(['trigger_id' => $trigger->id]);

            return $trigger;
        }

        /**
         * Versioned triggers are handled a bit differently than unversioned triggers.
         * Versions are stored almost like a linked list in the database. Version numbers
         * increase via a simple integer counter (1 -> 2 -> 3 -> etc...), and each versioned
         * trigger has a parent_id which points to the previous version's row in the table.
         * With this, we can build a chain of the triggerable asset's logic history.
         */
        $currentTrigger = $triggerable->trigger;

        // If conditions haven't changed and we're converting unversioned -> versioned,
        // just update the trigger's version in-place.
        if ($currentTrigger && $currentTrigger->conditions === $conditions) {
            // If conditions match and it's unversioned, convert to version 1.
            if ($currentTrigger->version === null) {
                $currentTrigger->update([
                    'version' => 1,
                    'user_id' => $user->id,
                ]);
                $triggerable->update(['trigger_id' => $currentTrigger->id]);
            }

            return $currentTrigger;
        }

        $latestVersion = $triggerable->triggers()->whereNotNull('version')->max('version') ?? 0;

        // If we ultimately made it here, create a new versioned trigger.
        $trigger = $triggerable->trigger()->save(new Trigger([
            'conditions' => $conditions,
            'version' => $latestVersion + 1,
            'parent_id' => $currentTrigger?->id,
            'user_id' => $user->id,
        ]));
        $triggerable->update(['trigger_id' => $trigger->id]);

        return $trigger;
    }
}
