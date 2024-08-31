<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript('User')]
class UserData extends Data
{
    public function __construct(
        public string $displayName,
        public string $avatarUrl,

        public Lazy|int $id,
        public Lazy|string|null $username,
        public Lazy|int|null $legacyPermissions,

        #[TypeScriptType([
            'prefersAbsoluteDates' => 'boolean',
        ])]
        public Lazy|array|null $preferences,

        #[LiteralTypeScriptType('App.Models.UserRole[]')]
        public Lazy|array|null $roles,

        public Lazy|int|null $unreadMessageCount,
    ) {
    }

    public static function fromRecentForumTopic(array $topic): self
    {
        return new self(
            displayName: $topic['AuthorDisplayName'] ?? $topic['Author'],
            avatarUrl: media_asset('UserPic/' . $topic['Author'] . '.png'),
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),

            legacyPermissions: null,
            preferences: null,
            roles: null,
            unreadMessageCount: null,
        );
    }

    public static function fromUser(User $user): self
    {
        return new self(
            displayName: $user->display_name,
            avatarUrl: $user->avatar_url,

            id: Lazy::create(fn () => $user->id),
            username: Lazy::create(fn () => $user->username),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            preferences: Lazy::create(
                fn () => [
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                ]
            ),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),
            unreadMessageCount: Lazy::create(fn () => $user->UnreadMessageCount),
        );
    }
}
