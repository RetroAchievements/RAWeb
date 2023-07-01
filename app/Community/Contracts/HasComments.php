<?php

declare(strict_types=1);

namespace App\Community\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasComments
{
    // @phpstan-ignore-next-line
    public function comments(): MorphMany;
}
