<?php

declare(strict_types=1);

namespace App\Community\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasComments
{
    public function comments(): MorphMany;
}
