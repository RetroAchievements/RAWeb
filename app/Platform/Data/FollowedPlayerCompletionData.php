<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('FollowedPlayerCompletion')]
class FollowedPlayerCompletionData extends Data
{
    public function __construct(
        public UserData $user,
        public PlayerGameData $playerGame,
    ) {
    }
}
