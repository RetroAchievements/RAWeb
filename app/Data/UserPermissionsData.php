<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserPermissions')]
class UserPermissionsData extends Data
{
    public function __construct(
        public Lazy|bool $manageGameHashes,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            manageGameHashes: $user->can('manage', \App\Models\GameHash::class)
        );
    }
}
