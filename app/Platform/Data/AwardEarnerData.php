<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AwardEarner')]
class AwardEarnerData extends Data
{
    public function __construct(
        public UserData $user,
        public Carbon $dateEarned,
    ) {
    }
}
