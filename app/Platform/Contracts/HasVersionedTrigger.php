<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

use App\Models\Trigger;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasVersionedTrigger
{
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
