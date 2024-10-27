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
        public Lazy|int $points,
        public Lazy|int $pointsSoftcore,

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

    // TODO remove this
    public static function fromRecentForumTopic(array $topic): self
    {
        return new self(
            displayName: $topic['AuthorDisplayName'] ?? $topic['Author'],
            avatarUrl: media_asset('UserPic/' . $topic['Author'] . '.png'),
            isMuted: false,
            mutedUntil: null,
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),

            apiKey: null,
            deletedAt: null,
            deleteRequested: null,
            emailAddress: null,
            legacyPermissions: null,
            locale: null,
            motto: '',
            points: 0,
            pointsSoftcore: 0,
            preferences: null,
            roles: null,
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
            // == eager fields
            displayName: $user->display_name,
            avatarUrl: $user->avatar_url,
            isMuted: $user->isMuted(),
            mutedUntil: $user->muted_until,

            // == lazy fields
            apiKey: Lazy::create(fn () => $user->APIKey),
            deletedAt: Lazy::create(fn () => $user->Deleted ? Carbon::parse($user->Deleted) : null),
            deleteRequested: Lazy::create(fn () => $user->DeleteRequested),
            emailAddress: Lazy::create(fn () => $user->EmailAddress),
            id: Lazy::create(fn () => $user->id),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            locale: Lazy::create(fn () => $user->locale === 'en' ? 'en_US' : $user->locale), // TODO remove conditional after renaming "en" to "en_US"
            motto: Lazy::create(fn () => $user->Motto),
            preferences: Lazy::create(
                fn () => [
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                ]
            ),
            points: Lazy::create(fn () => $user->points),
            pointsSoftcore: Lazy::create(fn () => $user->points_softcore),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),
            unreadMessageCount: Lazy::create(fn () => $user->UnreadMessageCount),
            username: Lazy::create(fn () => $user->username),
            userWallActive: Lazy::create(fn () => $user->UserWallActive),
            visibleRole: Lazy::create(fn () => $legacyPermissions > 1 ? Permissions::toString($legacyPermissions) : null),
            websitePrefs: Lazy::create(fn () => $user->websitePrefs),
        );
    }
}
