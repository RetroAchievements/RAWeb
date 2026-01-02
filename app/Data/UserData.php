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
        public Lazy|string|null $deleteRequestedAt = null,
        /** @deprecated use $isGone instead - it hides the delete date from page props */
        public Lazy|Carbon|null $deletedAt = null,
        /** @var RoleData[] */
        public Lazy|array|null $displayableRoles = null,
        public Lazy|string|null $email = null,
        public Lazy|bool|null $enableBetaFeatures = null,
        public Lazy|int $id = 0,
        public Lazy|bool $isBanned = false,
        public Lazy|bool $isEmailVerified = false,
        public Lazy|bool $isGone = false,
        public Lazy|bool $isMuted = false,
        public Lazy|bool $isNew = false,
        public Lazy|bool|null $isUserWallActive = null,
        public Lazy|Carbon|null $lastActivityAt = null,
        public Lazy|int|null $legacyPermissions = null,
        public Lazy|string|null $locale = null,
        public Lazy|string $motto = '',
        public Lazy|Carbon|null $mutedUntil = null,
        public Lazy|PlayerPreferredMode $playerPreferredMode = PlayerPreferredMode::Hardcore,
        public Lazy|int $points = 0,
        public Lazy|int $pointsSoftcore = 0,
        public Lazy|int|null $preferencesBitfield = null,
        public Lazy|string|null $richPresence = null,
        public Lazy|int|null $unreadMessages = null,
        public Lazy|string|null $username = null,
        public Lazy|RoleData|null $visibleRole = null,

        #[TypeScriptType([
            'isGloballyOptedOutOfSubsets' => 'boolean',
            'prefersAbsoluteDates' => 'boolean',
            'shouldAlwaysBypassContentWarnings' => 'boolean',
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
            avatarUrl: media_asset('UserPic/' . $topic['Author'] . '.png'),
            id: Lazy::create(fn () => (int) $topic['author_id']),
            username: Lazy::create(fn () => $topic['Author']),
        );
    }

    public static function fromUser(User $user): self
    {
        return new self(
            // == eager fields
            displayName: $user->display_name,
            avatarUrl: $user->avatar_url,

            // == lazy fields
            apiKey: Lazy::create(fn () => $user->web_api_key),
            createdAt: Lazy::create(fn () => Carbon::parse($user->created_at)),
            deletedAt: Lazy::create(fn () => $user->deleted_at ? Carbon::parse($user->deleted_at) : null),
            deleteRequestedAt: Lazy::create(fn () => $user->delete_requested_at),
            displayableRoles: Lazy::create(fn () => $user->displayableRoles),
            email: Lazy::create(fn () => $user->email),
            enableBetaFeatures: Lazy::create(fn () => $user->enable_beta_features),
            mutedUntil: Lazy::create(fn () => $user->muted_until),
            id: Lazy::create(fn () => $user->id),
            isBanned: Lazy::create(fn () => $user->isBanned()),
            isEmailVerified: Lazy::create(fn () => $user->isEmailVerified()),
            isGone: Lazy::create(fn () => $user->is_gone),
            isMuted: Lazy::create(fn () => $user->isMuted()),
            isNew: Lazy::create(fn () => $user->isNew()),
            isUserWallActive: Lazy::create(fn () => $user->is_user_wall_active),
            lastActivityAt: Lazy::create(fn () => $user->last_activity_at),
            legacyPermissions: Lazy::create(fn () => (int) $user->getAttribute('Permissions')),
            locale: Lazy::create(fn () => $user->locale === 'en' ? 'en_US' : $user->locale), // TODO remove conditional after renaming "en" to "en_US"
            motto: Lazy::create(fn () => $user->motto),
            preferences: Lazy::create(
                fn () => [
                    'isGloballyOptedOutOfSubsets' => $user->is_globally_opted_out_of_subsets,
                    'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                    'shouldAlwaysBypassContentWarnings' => $user->should_always_bypass_content_warnings,
                ]
            ),
            preferencesBitfield: Lazy::create(fn () => $user->preferences_bitfield),
            playerPreferredMode: Lazy::create(fn () => $user->player_preferred_mode),
            points: Lazy::create(fn () => $user->points_hardcore),
            pointsSoftcore: Lazy::create(fn () => $user->points),
            richPresence: Lazy::create(fn () => $user->rich_presence),
            roles: Lazy::create(fn () => $user->getRoleNames()->toArray()),
            unreadMessages: Lazy::create(fn () => $user->unread_messages),
            username: Lazy::create(fn () => $user->username),
            visibleRole: Lazy::create(fn () => $user->visible_role ? RoleData::fromRole($user->visible_role) : null),
        );
    }
}
