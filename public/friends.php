<?php

use RA\FriendshipType;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$friendsList = GetExtendedFriendsList($user);

function RenderFriendsList(string $header, string $user, array $friendsList, int $friendshipType, ?string $emptyMessage = null)
{
    $filteredList = array_filter($friendsList, function ($friend, $id) use ($friendshipType) {
        return $friend['Friendship'] == $friendshipType;
    }, ARRAY_FILTER_USE_BOTH);

    if (count($filteredList) == 0) {
        if ($emptyMessage != null) {
            echo "<h2>$header</h2>\n$emptyMessage\n";
        }
        return;
    }

    echo "<h2>$header</h2>";
    echo "<table><tbody>";
    echo "<tr><th style='width:70px'><th>User</th><th style='width:60%'>Last Seen</th><th style='width:128px'>Commands</th></tr>";
    foreach ($filteredList as $friendEntry) {
        echo "<tr>";

        $nextFriendName = $friendEntry['User'];
        $nextFriendActivity = $friendEntry['LastSeen'];

        echo "<td>";
        echo GetUserAndTooltipDiv($nextFriendName, true, null, 64);
        echo "</td>";

        echo "<td style='text-align:left'>";
        echo GetUserAndTooltipDiv($nextFriendName, false);
        echo "</td>";

        echo "<td>";
        if ($friendEntry['LastGameID']) {
            $gameData = getGameData($friendEntry['LastGameID']);
            echo GetGameAndTooltipDiv($gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName'], imgSizeOverride: 32);
            echo "<br/>";
        }
        echo "$nextFriendActivity";
        echo "</td>";

        echo "<td style='vertical-align:middle;'>";
        echo "<div>";
        switch ($friendshipType) {
            case FriendshipType::Friend:
                echo "<span style='display:block; line-height:1.6;'><a href='/createmessage.php?t=$user'>Send&nbsp;message</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::NotFriend . "'>End&nbsp;friendship</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::Blocked . "'>Block&nbsp;user</a></span>";
                break;
            case FriendshipType::Pending:
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::NotFriend . "'>Cancel&nbsp;request</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::Blocked . "'>Block&nbsp;user</a></span>";
                break;
            case FriendshipType::Requested:
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::Friend . "'>Confirm&nbsp;friendship</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::NotFriend . "'>Decline&nbsp;friendship</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::Blocked . "'>Block&nbsp;user</a></span>";
                break;
            case FriendshipType::Blocked:
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::Friend . "'>Request&nbsp;friendship</a></span>";
                echo "<span style='display:block; line-height:1.6;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=" . Friendshiptype::NotFriend . "'>Unblock&nbsp;user</a></span>";
                break;
        }
        echo "</div>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
}

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Friends");
?>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        RenderErrorCodeWarning($errorCode);
        RenderFriendsList('Friends', $user, $friendsList, FriendshipType::Friend, "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?");
        RenderFriendsList('Seeking Friendship', $user, $friendsList, FriendshipType::Requested);
        RenderFriendsList('Pending', $user, $friendsList, FriendshipType::Pending);
        RenderFriendsList('Blocked', $user, $friendsList, FriendshipType::Blocked);
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
