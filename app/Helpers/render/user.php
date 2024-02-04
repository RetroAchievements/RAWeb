<?php

use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

/**
 * Create the user and tooltip div that is shown when you hover over a username or user avatar.
 */
function userAvatar(
    string|User|null $user,
    ?bool $label = null,
    ?bool $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    ?string $link = null,
    bool|string|array $tooltip = true,
): string {
    if (!$user) {
        return '';
    }

    if (is_string($user)) {
        $username = $user;
        $user = Cache::store('array')->remember(
            CacheKey::buildUserCardDataCacheKey($username),
            Carbon::now()->addMonths(3),
            function () use ($username): ?array {
                $foundUser = User::firstWhere('User', $username);

                return $foundUser ? $foundUser->toArray() : null;
            }
        );

        if (!$user) {
            $userSanitized = $username;
            sanitize_outputs($userSanitized);

            $iconLabel = '';
            if ($icon !== false && ($icon || !$label)) {
                $iconLabel = "<img loading='lazy' width='$iconSize' height='$iconSize' src='" . media_asset('/UserPic/_User.png') . "' title='$userSanitized' alt='$userSanitized' class='$iconClass'>";
            }

            $usernameLabel = '';
            if ($label !== false && ($label || !$icon)) {
                $usernameLabel = "<del>$userSanitized</del>";
            }

            return "<span class='inline whitespace-nowrap'><span class='inline-block'>" . $iconLabel . $usernameLabel . "</span></span>";
        }
    }

    $username = $user['User'] ?? null;

    return avatar(
        resource: 'user',
        id: $username,
        label: $label !== false && ($label || !$icon) ? $username : null,
        link: $link ?: route('user.show', $username),
        tooltip: is_array($tooltip) ? renderUserCard($tooltip) : $tooltip,
        class: 'inline whitespace-nowrap',
        iconUrl: $icon !== false && ($icon || !$label) ? media_asset('/UserPic/' . $username . '.png') : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
    );
}

function renderUserCard(string|array $user): string
{
    return Blade::render('<x-user-card :user="$user" />', [
        'user' => $user,
    ]);
}

function RenderUserPref(
    int $websitePrefs,
    int $userPref,
    bool $setIfTrue,
    ?string $state = null,
    int $targetLoadingIcon = 1,
): void {
    echo "<input id='UserPreference$userPref' type='checkbox' ";
    echo "onchange='DoChangeUserPrefs($targetLoadingIcon); return false;' value='1'";

    if ($state) {
        echo " $state";
    } elseif (BitSet($websitePrefs, $userPref) === $setIfTrue) {
        echo " checked";
    }

    echo " />";
}
