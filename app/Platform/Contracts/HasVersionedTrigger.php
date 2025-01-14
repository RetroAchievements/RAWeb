<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

use App\Models\Trigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 */
interface HasVersionedTrigger
{
    /**
     * Get the latest trigger version via a denormalized trigger_id column.
     * Used for efficient reading of the current trigger.
     *
     * trigger() as a MorphOne must remain for Eloquent ORM polymorphic
     * queries to continue working correctly.
     *
     * @return BelongsTo<Trigger, TModel>
     */
    public function currentTrigger(): BelongsTo;

    /**
     * Get the latest trigger version.
     *
     * @return MorphOne<Trigger>
     */
    public function trigger(): MorphOne;

    /**
     * Get all trigger versions
     *
     * @return MorphMany<Trigger>
     */
    public function triggers(): MorphMany;
}
