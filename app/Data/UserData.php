<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Permissions;
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
        public bool $isMuted,

        public Lazy|int $id,
        public Lazy|string|null $username,
        public Lazy|string $motto,
        public Lazy|int|null $legacyPermissions,

        #[TypeScriptType([
            'prefersAbsoluteDates' => 'boolean',
        ])]
        public Lazy|array|null $preferences,

        #[LiteralTypeScriptType('App.Models.UserRole[]')]
        public Lazy|array|null $roles,

        public Lazy|string|null $apiKey,
        public Lazy|string|null $deleteRequested,
        public Lazy|string|null $emailAddress,
        public Lazy|int|null $unreadMessageCount,
        public Lazy|bool|null $userWallActive,
        public Lazy|string|null $visibleRole,
        public Lazy|int|null $websitePrefs,
    ) {
    }

    public static function fromRecentForumTopic(array $topic): self
    {
        return new self(
            displayName: $topic['AuthorDisplayName'] ?? $topic['Author'],
            avatarUrl: media_asset('UserPic/' . $topic['Author'] . '.png'),
            isMuted: false,
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),

            legacyPermissions: null,
            preferences: null,
            roles: null,
            motto: '',

            apiKey: null,
            deleteRequested: null,
            emailAddress: null,
            unreadMessageCount: null,
            userWallActive: null,
            visibleRole: null,
            websitePrefs: null,
        );
    }

    public static function fromUser(User $user): self
    {
        $legacyPermissions = (int) $user->getAttribute('Permissions');

        return new self(
            displayName: $user->display_name,
            avatarUrl: $user->avatar_url,
            isMuted: $user->isMuted(),

            id: Lazy::create(fn () => $user->id),
            username: Lazy::create(fn () => $user->username),
            motto: Lazy::create(fn () => $user->Motto),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            preferences: Lazy::create(
                fn () => [
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                ]
            ),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),

            apiKey: Lazy::create(fn () => $user->APIKey),
            deleteRequested: Lazy::create(fn () => $user->DeleteRequested),
            emailAddress: Lazy::create(fn () => $user->EmailAddress),
            unreadMessageCount: Lazy::create(fn () => $user->UnreadMessageCount),
            userWallActive: Lazy::create(fn () => $user->UserWallActive),
            visibleRole: Lazy::create(fn () => $legacyPermissions > 1 ? Permissions::toString($legacyPermissions) : null),
            websitePrefs: Lazy::create(fn () => $user->websitePrefs),
        );
    }
}
