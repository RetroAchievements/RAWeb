<?php

use App\Community\Enums\UserRelationship;
use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$followingList = [];
$blockedUsersList = [];
foreach (GetExtendedFriendsList($user) as $entry) {
    switch ($entry['Friendship']) {
        case UserRelationship::Following:
            $followingList[] = $entry;
            break;
        case UserRelationship::Blocked:
            $blockedUsersList[] = $entry['User'];
            break;
    }
}
// GetExtendedFriendsList() returns most recent users first. sort by name for block list
asort($blockedUsersList);

$followersList = GetFollowers($user);
?>
<x-app-layout pageTitle="Following">
    <h2>Following</h2>
    <?php
    if (empty($followingList)) {
        echo "You don't appear to be following anyone yet. Why not <a href='/userList.php'>browse the user pages</a> to find someone to add to follow?<br>";
    } else {
        echo "<table class='table-highlight'><tbody>";
        foreach ($followingList as $entry) {
            echo "<tr>";

            $followingUser = $entry['User'];

            echo "<td>";
            echo userAvatar($followingUser, label: false, iconSize: 42);
            echo "</td>";

            echo "<td>";
            echo userAvatar($followingUser, icon: false);
            echo "</td>";

            echo "<td class='w-full'>";
            if ($entry['LastActivityTimestamp']) {
                echo '<div>Last seen ' . getNiceDate(strtotime($entry['LastActivityTimestamp'])) . '<div>';
            }
            if ($entry['LastGameID']) {
                $gameData = getGameData($entry['LastGameID']);
                echo '<div>';
                echo '<small>';
                echo gameAvatar($gameData, iconSize: 16);
                echo '</small>';
                echo '</div>';
            }

            echo '<div>';
            $activity = $entry['LastSeen'];
            sanitize_outputs($activity);
            echo '<small>' . $activity . '</small>';
            echo '</div>';
            echo "</td>";

            echo "<td style='vertical-align:middle;'>";
            echo "<div class='flex justify-end gap-2'>";

            echo "<a class='btn btn-link' href='" . route('message.create') . "?to=$followingUser'>Message</a>";

            echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='user' value='$followingUser'>";
            echo "<input type='hidden' name='action' value='" . UserRelationship::NotFollowing . "'>";
            echo "<button class='btn btn-link'>Unfollow</button>";
            echo "</form>";

            echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='user' value='$followingUser'>";
            echo "<input type='hidden' name='action' value='" . UserRelationship::Blocked . "'>";
            echo "<button class='btn btn-link'>Block</button>";
            echo "</form>";

            echo "</div>";
            echo "</td>";

            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    RenderUserList('Followers', $followersList, UserRelationship::Following, $followingList);
    RenderUserList('Blocked', $blockedUsersList, UserRelationship::Blocked, $followingList);
    ?>
    @if (!empty($followingList))
        <x-slot name="sidebar">
            <?php
            RenderPointsRankingComponent($user, true);
            ?>
        </x-slot>
    @endif
</x-app-layout>
