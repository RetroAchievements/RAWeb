<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        public ?Carbon $mutedUntil,

        public Lazy|int $id,
        public Lazy|string|null $username,
        public Lazy|int|null $legacyPermissions,
        public Lazy|string|null $locale,
        public Lazy|string $motto,

        #[TypeScriptType([
            'prefersAbsoluteDates' => 'boolean',
        ])]
        public Lazy|array|null $preferences,

        #[LiteralTypeScriptType('App.Models.UserRole[]')]
        public Lazy|array|null $roles,

        public Lazy|string|null $apiKey,
        public Lazy|string|null $deleteRequested,
        public Lazy|Carbon|null $deletedAt,
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
            mutedUntil: null,
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),

            legacyPermissions: null,
            locale: null,
            motto: '',
            preferences: null,
            roles: null,

            apiKey: null,
            deleteRequested: null,
            deletedAt: null,
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
            mutedUntil: $user->muted_until,

            id: Lazy::create(fn () => $user->id),
            username: Lazy::create(fn () => $user->username),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            // TODO remove conditional after renaming "en" to "en_US"
            locale: Lazy::create(fn () => $user->locale === 'en' ? 'en_US' : $user->locale),
            motto: Lazy::create(fn () => $user->Motto),
            preferences: Lazy::create(
                fn () => [
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                ]
            ),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),

            apiKey: Lazy::create(fn () => $user->APIKey),
            deleteRequested: Lazy::create(fn () => $user->DeleteRequested),
            deletedAt: Lazy::create(fn () => $user->Deleted ? Carbon::parse($user->Deleted) : null),
            emailAddress: Lazy::create(fn () => $user->EmailAddress),
            unreadMessageCount: Lazy::create(fn () => $user->UnreadMessageCount),
            userWallActive: Lazy::create(fn () => $user->UserWallActive),
            visibleRole: Lazy::create(fn () => $legacyPermissions > 1 ? Permissions::toString($legacyPermissions) : null),
            websitePrefs: Lazy::create(fn () => $user->websitePrefs),
        );
    }
}
