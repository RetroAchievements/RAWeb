<?php

declare(strict_types=1);

namespace App\Support\Sync;

class SyncEntity
{
    public function __construct(
        public string $reference,
        public array $data,
    ) {
    }
}
