<?php

use RA\Permissions;
use RA\UserRelationship;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$errorCode = requestInputSanitized('e');

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

$followersList = GetFollowers($user);

function RenderUserList(string $header, string $user, array $users, int $friendshipType, ?string $emptyMessage = null)
{
    if (count($users) == 0) {
        return;
    }

    echo "<br/><h2>$header</h2>";
    echo "<table><tbody>";
    echo "<tr><th style='width:36px'><th>User</th><th style='width:128px'></th></tr>";
    foreach ($users as $user) {
        echo "<tr>";

        echo "<td>";
        echo GetUserAndTooltipDiv($user, true);
        echo "</td>";

        echo "<td style='text-align:left'>";
        echo GetUserAndTooltipDiv($user);
        echo "</td>";

        echo "<td style='vertical-align:middle;'>";
        echo "<div>";
        switch ($friendshipType) {
            case UserRelationship::Following:
                echo "<span style='display:block; line-height:1.6;'><a href='/request/user/update-relationship.php?f=$user&amp;a=" . UserRelationship::Blocked . "'>Block&nbsp;user</a></span>";
                break;
            case UserRelationship::Blocked:
                echo "<span style='display:block; line-height:1.6;'><a href='/request/user/update-relationship.php?f=$user&amp;a=" . UserRelationship::NotFollowing . "'>Unblock&nbsp;user</a></span>";
                break;
        }
        echo "</div>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
}

RenderHtmlStart();
RenderHtmlHead("Following");
?>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        RenderErrorCodeWarning($errorCode);

        echo "<h2>Following</h2>";
        if (empty($followingList)) {
            echo "You don't appear to be following anyone yet. Why not <a href='/userList.php'>browse the user pages</a> to find someone to add to follow?<br>";
        } else {
            echo "<table><tbody>";
            echo "<tr><th style='width:70px'><th>User</th><th style='width:60%'>Last Seen</th><th style='width:128px'></th></tr>";
            foreach ($followingList as $entry) {
                echo "<tr>";

                $followingUser = $entry['User'];

                echo "<td>";
                echo GetUserAndTooltipDiv($followingUser, true, null, 64);
                echo "</td>";

                echo "<td style='text-align:left'>";
                echo GetUserAndTooltipDiv($followingUser, false);
                echo "</td>";

                echo "<td>";
                if ($entry['LastGameID']) {
                    $gameData = getGameData($entry['LastGameID']);
                    echo GetGameAndTooltipDiv($gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName']);
                    echo "<br/>";
                }

                $activity = $entry['LastSeen'];
                sanitize_outputs($activity);
                echo $activity;
                echo "</td>";

                echo "<td style='vertical-align:middle;'>";
                echo "<div>";
                echo "<span style='display:block; line-height:1.6;'><a href='/createmessage.php?t=$user'>Send&nbsp;message</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/user/update-relationship.php?f=$followingUser&amp;a=" . UserRelationship::NotFollowing . "'>Stop&nbsp;Following</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/user/update-relationship.php?f=$followingUser&amp;a=" . UserRelationship::Blocked . "'>Block&nbsp;user</a></span>";
                echo "</div>";
                echo "</td>";

                echo "</tr>";
            }
            echo "</tbody></table>";
        }

        RenderUserList('Followers', $user, $followersList, UserRelationship::Following);
        RenderUserList('Blocked', $user, $blockedUsersList, UserRelationship::Blocked);
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
