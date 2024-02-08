<?php

use App\Community\Enums\UserRelationship;
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

function RenderUserList(string $header, array $users, int $friendshipType, array $followingList): void
{
    if (count($users) == 0) {
        return;
    }

    echo "<br/><h2>$header</h2>";
    echo "<table class='table-highlight'><tbody>";
    foreach ($users as $user) {
        echo "<tr>";

        echo "<td>";
        echo userAvatar($user, label: false);
        echo "</td>";

        echo "<td class='w-full'>";
        echo userAvatar($user, icon: false);
        echo "</td>";

        echo "<td style='vertical-align:middle;'>";
        echo "<div class='flex justify-end gap-2'>";
        switch ($friendshipType) {
            case UserRelationship::Following:
                if (!in_array($user, array_column($followingList, 'User'))) {
                    echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='user' value='$user'>";
                    echo "<input type='hidden' name='action' value='" . UserRelationship::Following . "'>";
                    echo "<button class='btn btn-link'>Follow</button>";
                    echo "</form>";
                }
                echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
                echo csrf_field();
                echo "<input type='hidden' name='user' value='$user'>";
                echo "<input type='hidden' name='action' value='" . UserRelationship::Blocked . "'>";
                echo "<button class='btn btn-link'>Block</button>";
                echo "</form>";
                break;
            case UserRelationship::Blocked:
                echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
                echo csrf_field();
                echo "<input type='hidden' name='user' value='$user'>";
                echo "<input type='hidden' name='action' value='" . UserRelationship::NotFollowing . "'>";
                echo "<button class='btn btn-link'>Unblock</button>";
                echo "</form>";
                break;
        }
        echo "</div>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
}
