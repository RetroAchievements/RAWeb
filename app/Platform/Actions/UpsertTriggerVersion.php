<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Models\Trigger;
use Illuminate\Database\Eloquent\Model;

class UpsertTriggerVersion
{
    public function execute(Model $triggerable, string $conditions, bool $versioned = true): ?Trigger
    {
        if (!$triggerable instanceof HasVersionedTrigger) {
            return null;
        }

        $triggerable->loadMissing('trigger');

        /** @var ?Trigger $currentTrigger */
        $currentTrigger = $triggerable->trigger;

        /*
         * No trigger exists yet -> create one
         * Attention: when $versioned = false make sure to check if another entry should even be created beforehand
         * Otherwise the unique check will not be triggered and multiple unversioned triggers will be created
         */
        if (!$currentTrigger) {
            $trigger = new Trigger([
                'conditions' => $conditions,
                'version' => $versioned ? 1 : null,
            ]);
            /** @var Trigger $newTrigger */
            $newTrigger = $triggerable->trigger()->save($trigger);

            return $newTrigger;
        }

        if ($currentTrigger->conditions === $conditions) {
            return $currentTrigger;
        }

        $trigger = new Trigger([
            'conditions' => $conditions,
            'version' => $versioned ? $currentTrigger->version + 1 : null,
            'parent_id' => $currentTrigger->id,
        ]);
        /** @var Trigger $newTrigger */
        $newTrigger = $triggerable->trigger()->save($trigger);

        return $newTrigger;
    }
}
