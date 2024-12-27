<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
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

        // If no user is given, then find the model's last editor/developer from system comments.
        // Fall back to the model's user foreign key if one exists.
        $userId = $user?->id ?? $this->findLastEditor($triggerable)?->id;

        $triggerable->loadMissing('trigger');

        // For unversioned triggers, explicitly check if one already exists.
        // MySQL/MariaDB ignore uniqueness constraints on NULL column values (version),
        // so if we're not careful, we can wind up with lots of unversioned triggers
        // for the same triggerable asset (achievement).
        if (!$versioned) {
            $currentTrigger = $triggerable->triggers()->unversioned()->first();

            if (!$currentTrigger && $triggerable->triggers()->unversioned()->exists()) {
                throw new RuntimeException('Multiple unversioned triggers detected for ' . get_class($triggerable) . ' #' . $triggerable->getKey());
            }

            // If there's already an unversioned trigger for this asset, just update it in-place.
            if ($currentTrigger) {
                $currentTrigger->update([
                    'conditions' => $conditions,
                    'user_id' => $userId,
                ]);

                return $currentTrigger;
            }

            // Otherwise, create a new unversioned trigger. It'll get its first version on publish.
            return $triggerable->trigger()->save(new Trigger([
                'conditions' => $conditions,
                'version' => null,
                'user_id' => $userId,
            ]));
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
        if ($currentTrigger && $currentTrigger->conditions === $conditions && $currentTrigger->version === null) {
            $currentTrigger->update([
                'version' => 1,
                'user_id' => $userId,
            ]);

            return $currentTrigger;
        }

        // If nothing changed at all, bail.
        if ($currentTrigger && $currentTrigger->conditions === $conditions) {
            return $currentTrigger;
        }

        // If we ultimately made it here, create a new versioned trigger.
        return $triggerable->trigger()->save(new Trigger([
            'conditions' => $conditions,
            'version' => ($currentTrigger->version ?? 0) + 1,
            'parent_id' => $currentTrigger?->id,
            'user_id' => $userId,
        ]));
    }

    private function findLastEditor(Model $triggerable): ?User
    {
        if ($triggerable instanceof Game) {
            $lastRichPresenceEdit = $triggerable->modificationsComments()
                ->automated()
                ->whereRaw("LOWER(Payload) LIKE '%changed the rich presence%'")
                ->latest('Submitted')
                ->first();

            if ($lastRichPresenceEdit) {
                $username = explode(' ', $lastRichPresenceEdit->Payload)[0];

                return $this->findUserByName($username);
            }

            return null;
        }

        if ($triggerable instanceof Leaderboard) {
            $lastLeaderboardEdit = $triggerable->comments()
                ->automated()
                ->whereRaw("LOWER(Payload) LIKE '% edited this leaderboard%'")
                ->latest('Submitted')
                ->first();

            if ($lastLeaderboardEdit) {
                $username = explode(' ', $lastLeaderboardEdit->Payload)[0];

                return $this->findUserByName($username);
            }

            return User::find($triggerable->author_id);
        }

        if ($triggerable instanceof Achievement) {
            $lastLogicEdit = $triggerable->comments()
                ->automated()
                ->whereRaw("LOWER(Payload) LIKE '% edited%logic%'")
                ->latest('Submitted')
                ->first();

            if ($lastLogicEdit) {
                $username = explode(' ', $lastLogicEdit->Payload)[0];

                return $this->findUserByName($username);
            }

            return User::find($triggerable->user_id) ?? null;
        }

        return null;
    }

    private function findUserByName(string $name): ?User
    {
        return User::withTrashed()
            ->where('display_name', $name)
            ->orWhere('User', $name)
            ->first();
    }
}
