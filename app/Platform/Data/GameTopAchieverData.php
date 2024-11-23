<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameTopAchiever')]
class GameTopAchieverData extends Data
{
    public function __construct(
        public int $rank,
        public UserData $user,
        public int $score,
        public ?PlayerBadgeData $badge,
    ) {
    }
}
