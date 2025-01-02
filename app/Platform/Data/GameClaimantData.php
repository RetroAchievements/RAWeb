<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameClaimant')]
class GameClaimantData extends Data
{
    public function __construct(
        public UserData $user,
        public string $claimType, // "primary" or "collaboration"
    ) {
    }

    public static function fromUser(User $user, string $claimType): self
    {
        return new self(
            user: UserData::from($user),
            claimType: $claimType,
        );
    }
}
