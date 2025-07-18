<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use App\Platform\Enums\PlayerPreferredMode;
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

        public Lazy|string|null $apiKey = null,
        public Lazy|Carbon|null $createdAt = null,
        public Lazy|string|null $deleteRequested = null,
        public Lazy|Carbon|null $deletedAt = null,
        /** @var RoleData[] */
        public Lazy|array|null $displayableRoles = null,
        public Lazy|string|null $emailAddress = null,
        public Lazy|int $id = 0,
        public Lazy|bool $isEmailVerified = false,
        public Lazy|bool $isMuted = false,
        public Lazy|bool $isNew = false,
        public Lazy|Carbon|null $lastActivityAt = null,
        public Lazy|int|null $legacyPermissions = null,
        public Lazy|string|null $locale = null,
        public Lazy|string $motto = '',
        public Lazy|Carbon|null $mutedUntil = null,
        public Lazy|PlayerPreferredMode $playerPreferredMode = PlayerPreferredMode::Hardcore,
        public Lazy|int $points = 0,
        public Lazy|int $pointsSoftcore = 0,
        public Lazy|string|null $richPresenceMsg = null,
        public Lazy|int|null $unreadMessageCount = null,
        public Lazy|string|null $username = null,
        public Lazy|bool|null $userWallActive = null,
        public Lazy|RoleData|null $visibleRole = null,
        public Lazy|int|null $websitePrefs = null,

        #[TypeScriptType([
            'shouldAlwaysBypassContentWarnings' => 'boolean',
            'prefersAbsoluteDates' => 'boolean',
        ])]
        public Lazy|array|null $preferences = [],
        #[LiteralTypeScriptType('App.Models.UserRole[]')]
        public Lazy|array|null $roles = [],
    ) {
    }

    public static function fromRecentForumTopic(array $topic): self
    {
        return new self(
            displayName: $topic['AuthorDisplayName'] ?? $topic['Author'],
            avatarUrl: $topic['Author'] . '.png',
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),
        );
    }

    public static function fromUser(User $user): self
    {
        return new self(
            // == eager fields
            displayName: $user->display_name,
            avatarUrl: "{$user->username}.png",

            // == lazy fields
            apiKey: Lazy::create(fn () => $user->APIKey),
            createdAt: Lazy::create(fn () => Carbon::parse($user->Created)),
            deletedAt: Lazy::create(fn () => $user->Deleted ? Carbon::parse($user->Deleted) : null),
            deleteRequested: Lazy::create(fn () => $user->DeleteRequested),
            displayableRoles: Lazy::create(fn () => $user->displayableRoles),
            emailAddress: Lazy::create(fn () => $user->EmailAddress),
            mutedUntil: Lazy::create(fn () => $user->muted_until),
            id: Lazy::create(fn () => $user->id),
            isEmailVerified: Lazy::create(fn () => $user->isEmailVerified()),
            isMuted: Lazy::create(fn () => $user->isMuted()),
            isNew: Lazy::create(fn () => $user->isNew()),
            lastActivityAt: Lazy::create(fn () => $user->LastLogin),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            locale: Lazy::create(fn () => $user->locale === 'en' ? 'en_US' : $user->locale), // TODO remove conditional after renaming "en" to "en_US"
            motto: Lazy::create(fn () => $user->Motto),
            preferences: Lazy::create(
                fn () => [
                    'shouldAlwaysBypassContentWarnings' => $user->should_always_bypass_content_warnings,
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                ]
            ),
            playerPreferredMode: Lazy::create(fn () => $user->player_preferred_mode),
            points: Lazy::create(fn () => $user->points),
            pointsSoftcore: Lazy::create(fn () => $user->points_softcore),
            richPresenceMsg: Lazy::create(fn () => $user->RichPresenceMsg),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),
            unreadMessageCount: Lazy::create(fn () => $user->UnreadMessageCount),
            username: Lazy::create(fn () => $user->username),
            userWallActive: Lazy::create(fn () => $user->UserWallActive),
            visibleRole: Lazy::create(fn () => $user->visible_role ? RoleData::fromRole($user->visible_role) : null),
            websitePrefs: Lazy::create(fn () => $user->websitePrefs),
        );
    }
}
