<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasVersionedTrigger
{
    public function trigger(): MorphOne;
}
