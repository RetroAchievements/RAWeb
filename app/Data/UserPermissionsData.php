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
        public Lazy|bool $develop,
        public Lazy|bool $manageGameHashes,
        public Lazy|bool $manipulateApiKeys,
        public Lazy|bool $updateAvatar,
        public Lazy|bool $updateMotto,
    ) {
    }

    public static function fromUser(?User $user): self
    {
        return new self(
            develop: Lazy::create(fn () => $user ? $user->can('develop') : false),
            manageGameHashes: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\GameHash::class) : false),
            manipulateApiKeys: Lazy::create(fn () => $user ? $user->can('manipulateApiKeys', $user) : false),
            updateAvatar: Lazy::create(fn () => $user ? $user->can('updateAvatar', $user) : false),
            updateMotto: Lazy::create(fn () => $user ? $user->can('updateMotto', $user) : false),
        );
    }
}
