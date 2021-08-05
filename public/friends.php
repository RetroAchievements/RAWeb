<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Unregistered)) {
    if (getAccountDetails($user, $userDetails) == false) {
        //	Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    //	Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$cookie = $userDetails['cookie'];

$friendsList = getFriendList($user);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Friends");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Friends</h2>
        <?php
        if (!isset($friendsList)) {
            echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br>";
        } else {
            echo "<table><tbody>";
            echo "<tr><th colspan='2'>Friend</th><th>Last Seen</th><th>Commands</th></tr>";
            $iter = 0;
            foreach ($friendsList as $friendEntry) {
                if ($iter++ % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr>";
                }

                $nextFriendName = $friendEntry['Friend'];
                $nextFriendActivity = $friendEntry['LastSeen'];

                echo "<td>";
                echo GetUserAndTooltipDiv($nextFriendName, true, null, 64);
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv($nextFriendName, false);
                echo "</td>";

                echo "<td>";
                echo "$nextFriendActivity";
                echo "</td>";

                echo "<td style='vertical-align:middle;'>";
                echo "<div class='buttoncollection'>";
                echo "<span style='display:block;'><a href='/createmessage.php?t=$user'>Send&nbsp;Message</a></span>";
                echo "<span style='display:block;'><a href='/request/friend/update.php?u=$user&amp;c=$cookie&amp;f=$nextFriendName&amp;a=0'>Remove&nbsp;Friend</a></span>";
                echo "<span style='display:block;'><a href='/request/friend/update.php?u=$user&amp;c=$cookie&amp;f=$nextFriendName&amp;a=-1'>Block&nbsp;User</a></span>";
                echo "</div>";
                echo "</td>";

                echo "</tr>";
            }
            echo "</tbody></table>";
        }
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

