<?php

declare(strict_types=1);

namespace App\Support\Sync;

class SyncStrategy
{
    public const INSERT_IGNORE = 'insert-ignore';

    public const UPSERT = 'upsert';
}
