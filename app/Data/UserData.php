<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript('User')]
class UserData extends Data
{
    public function __construct(
        public string $avatarUrl,
        public string $displayName,
        public int $id,
        public int $legacyPermissions,

        #[TypeScriptType([
            'prefersAbsoluteDates' => 'boolean',
        ])]
        public array $preferences,

        #[LiteralTypeScriptType('App.Models.UserRole[]')]
        public array $roles,

        public int $unreadMessageCount,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            avatarUrl: $user->avatar_url,
            displayName: $user->display_name,
            id: $user->id,
            legacyPermissions: (int) $user->getAttribute('Permissions'),
            preferences: [
                'prefersAbsoluteDates' => $user->prefers_absolute_dates,
            ],
            roles: $user->getRoleNames()->toArray(),
            unreadMessageCount: $user->UnreadMessageCount,
        );
    }
}
