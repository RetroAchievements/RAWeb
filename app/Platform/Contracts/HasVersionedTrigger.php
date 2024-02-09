<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

use App\Models\Trigger;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasVersionedTrigger
{
    /**
     * @return MorphOne<Trigger>
     */
    public function trigger(): MorphOne;
}
