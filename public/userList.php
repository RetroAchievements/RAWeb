<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$sortBy = requestInputQuery('s', null, 'integer');
$offset = requestInputQuery('o', null, 'integer');
$maxCount = 25;

$perms = requestInputQuery('p', 1, 'integer');

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$showUntracked = false;
if (isset($user) && $permissions >= \RA\Permissions::Admin) {
    $showUntracked = requestInputSanitized('u', null, 'boolean');
} elseif ($perms < \RA\Permissions::Unregistered || $perms > \RA\Permissions::Admin) {
    $perms = 1;
}

$userCount = getUserListByPerms($sortBy, $offset, $maxCount, $userListData, $user, $perms, $showUntracked);

$permissionName = null;
if ($perms >= \RA\Permissions::Spam && $perms <= \RA\Permissions::Admin) {
    $permissionName = PermissionsToString($perms);
} elseif ($showUntracked) { // meleu: using -99 magic number for untracked (I know, it's sloppy)
    $perms = -99;
    $permissionName = "Untracked";
}

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead("Users");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<b>All Users";

        if ($permissionName != null) {
            echo " &raquo; $permissionName";
            if ($showUntracked && $permissionName != "Untracked") {
                echo " (including Untracked)";
            }
        }

        echo "</b></div>";

        echo "<div class='largelist'>";
        echo "<h2 class='longheader'>User List:</h2>";

        echo "<p>Filter: ";

        if ($perms == \RA\Permissions::Unregistered) {
            echo "<b>Unregistered</b>";
        } else {
            echo "<a href='/userList.php?s=$sortBy&p=0'>Unregistered</a>";
        }

        for ($i = \RA\Permissions::Registered; $i <= \RA\Permissions::Admin; $i++) {
            echo " | ";

            if (!$showUntracked && $i == $perms && is_int($perms)) {
                echo "<b>" . PermissionsToString($i) . "</b>";
            } else {
                echo "<a href='/userList.php?s=$sortBy&p=$i'>" . PermissionsToString($i) . "</a>";
            }
        }
        echo "</p>";

        if (isset($user) && $permissions >= \RA\Permissions::Admin) {
            echo "<p>";
            echo "Filters for admins (always includes Untracked users):<br>";
            if ($permissionName == "Untracked") {
                echo "<b>All Untracked users</b>";
            } else {
                echo "<a href='/userList.php?s=$sortBy&u=1&p=-99'>All Untracked users</a>";
            }

            for ($i = \RA\Permissions::Spam; $i <= \RA\Permissions::Admin; $i++) {
                echo " | ";

                if ($showUntracked && $i == $perms && is_int($perms)) {
                    echo "<b>" . PermissionsToString($i) . "</b>";
                } else {
                    echo "<a href='/userList.php?s=$sortBy&u=1&p=$i'>" . PermissionsToString($i) . "</a>";
                }
            }
            echo "</p>";
        }

        echo "<div class='table-wrapper'><table><tbody>";

        $sort1 = ($sortBy == 1) ? 11 : 1;
        $sort2 = ($sortBy == 2) ? 12 : 2;
        $sort3 = ($sortBy == 3) ? 13 : 3;
        $sort4 = ($sortBy == 4) ? 14 : 4;

        if (($sortBy == 2)) {
            echo "<th>Rank</th>";
        }

        echo "<th colspan='2'><a href='/userList.php?s=$sort1&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>User</a></th>";
        echo "<th><a href='/userList.php?s=$sort2&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Points</a></th>";
        echo "<th><a href='/userList.php?s=$sort3&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Num Achievements Earned</a></th>";
        echo "<th><a href='/userList.php?s=$sort4&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Last Login</a></th>";

        $userCount = 0;
        foreach ($userListData as $userEntry) {
            if ($userCount++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class=\"alt\">";
            }

            $nextUser = $userEntry['User'];
            $totalPoints = $userEntry['RAPoints'];
            $totalEarned = $userEntry['NumAwarded'];
            $lastLogin = getNiceDate(strtotime($userEntry['LastLogin']));

            if (($sortBy == 2)) {
                echo "<td>";
                // echo $userCount + $offset;
                echo getUserRank($nextUser);
                echo "</td>";
            }

            echo "<td>";
            echo GetUserAndTooltipDiv($nextUser, true);
            echo "</td>";
            echo "<td class='user'>";
            echo GetUserAndTooltipDiv($nextUser, false);
            echo "</td>";

            echo "<td>$totalPoints</td>";

            echo "<td>$totalEarned</td>";

            echo "<td>$lastLogin</td>";

            echo "</tr>";
        }
        echo "</tbody></table></div>";

        echo "<div class='rightalign row'>";
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            echo "<a href='/userList.php?s=$sortBy&amp;o=$prevOffset&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>&lt; Previous $maxCount</a> - ";
        }
        if ($userCount == $maxCount) {
            // Max number fetched, i.e. there are more. Can goto next 25.
            $nextOffset = $offset + $maxCount;
            echo "<a href='/userList.php?s=$sortBy&amp;o=$nextOffset&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Next $maxCount &gt;</a>";
        }
        echo "</div>";

        echo "</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
