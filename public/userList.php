<?php

use App\Site\Enums\Permissions;

$sortBy = (int) request()->query('s');
$offset = (int) request()->query('o');
$maxCount = 25;

$perms = (int) request()->query('p', '1');

authenticateFromCookie($user, $permissions, $userDetails);

$showUntracked = false;
if (isset($user) && $permissions >= Permissions::Moderator) {
    $showUntracked = requestInputSanitized('u', null, 'boolean');
} elseif ($perms < Permissions::Unregistered || $perms > Permissions::Moderator) {
    $perms = 1;
}

$userCount = getUserListByPerms($sortBy, $offset, $maxCount, $userListData, $user, $perms, $showUntracked);

$permissionName = null;
if ($perms >= Permissions::Spam && $perms <= Permissions::Moderator) {
    $permissionName = Permissions::toString($perms);
} elseif ($showUntracked) { // meleu: using -99 magic number for untracked (I know, it's sloppy)
    $perms = -99;
    $permissionName = "Untracked";
}

RenderContentStart("Users");
?>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<b>" . __res('user');

    if ($permissionName != null) {
        echo " &raquo; $permissionName";
        if ($showUntracked && $permissionName != "Untracked") {
            echo " (including Untracked)";
        }
    }

    echo "</b></div>";

    echo "<div>";
    echo "<h2>" . __res('user') . "</h2>";

    echo "<p class='embedded'>Filter: ";

    $permLinks = [];
    foreach (Permissions::assignable() as $i) {
        if (!$showUntracked && $i == $perms && is_int($perms)) {
            $permLinks[] = "<b>" . Permissions::toString($i) . "</b>";
        } else {
            $permLinks[] = "<a href='/userList.php?s=$sortBy&p=$i'>" . Permissions::toString($i) . "</a>";
        }
    }
    echo implode(' | ', $permLinks);
    echo "</p>";

    if (isset($user) && $permissions >= Permissions::Moderator) {
        echo "<p class='embedded'>";
        echo "Filters for admins (always includes Untracked users):<br>";
        if ($permissionName == "Untracked") {
            echo "<b>All Untracked users</b>";
        } else {
            echo "<a href='/userList.php?s=$sortBy&u=1&p=-99'>All Untracked users</a>";
        }

        foreach (Permissions::cases() as $i) {
            echo " | ";

            if ($showUntracked && $i == $perms && is_int($perms)) {
                echo "<b>" . Permissions::toString($i) . "</b>";
            } else {
                echo "<a href='/userList.php?s=$sortBy&u=1&p=$i'>" . Permissions::toString($i) . "</a>";
            }
        }
        echo "</p>";
    }

    echo "<div class='table-wrapper'><table class='table-highlight'><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;

    echo "<tr class='do-not-highlight'>";
    if ($sortBy == 2) {
        echo "<th>Rank</th>";
    }
    echo "<th><a href='/userList.php?s=$sort1&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>User</a></th>";
    echo "<th><a href='/userList.php?s=$sort2&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Points</a></th>";
    echo "<th><a href='/userList.php?s=$sort3&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Achievements</a></th>";
    echo "<th><a href='/userList.php?s=$sort4&p=$perms" . ($showUntracked ? "&u=1" : '') . "'>Last Login</a></th>";
    echo "</tr>";

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
        $lastLogin = !empty($userEntry['LastLogin']) ? getNiceDate(strtotime($userEntry['LastLogin'])) : '';

        if ($sortBy == 2) {
            echo "<td>";
            // echo $userCount + $offset;
            echo getUserRank($nextUser);
            echo "</td>";
        }

        echo "<td>";
        echo userAvatar($nextUser);
        echo "</td>";

        echo "<td>$totalPoints</td>";

        echo "<td>$totalEarned</td>";

        echo "<td>$lastLogin</td>";

        echo "</tr>";
    }
    echo "</tbody></table></div>";

    echo "<div class='text-right'>";
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
</article>
<?php RenderContentEnd(); ?>
