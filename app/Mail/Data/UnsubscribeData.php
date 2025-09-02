<?php

declare(strict_types=1);

namespace App\Mail\Data;

use Spatie\LaravelData\Data;

abstract class UnsubscribeData extends Data
{
    public function __construct(
        public int $userId,
        public string $type,
    ) {
    }
}
