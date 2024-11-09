<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('CurrentlyOnline')]
class CurrentlyOnlineData extends Data
{
    public function __construct(
        /**
         * @var int[]
         */
        public array $logEntries,

        public int $numCurrentPlayers,
        public int $allTimeHighPlayers,
        public ?Carbon $allTimeHighDate,
    ) {
    }
}
