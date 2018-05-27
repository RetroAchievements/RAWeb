<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$sortBy = seekGet( 's' );
$offset = seekGet( 'o' );
$maxCount = 25;

$perms = seekGet( 'p' );

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$showUntracked = FALSE;
if( isset( $user ) && $permissions >= \RA\Permissions::Admin )
{
    $showUntracked = seekGET( 'u' );
    settype( $showUntracked, 'boolean' );
    if( $showUntracked )
        $userCount = getUserListByPerms( $sortBy, $offset, $maxCount, $userListData, $user, $perms, TRUE);
    else
        $userCount = getUserListByPerms( $sortBy, $offset, $maxCount, $userListData, $user , $perms );
}
else
    $userCount = getUserListByPerms( $sortBy, $offset, $maxCount, $userListData, $user , $perms );

$permissionName = NULL;
if( $perms >= \RA\Permissions::Spam && $perms <= \RA\Permissions::Admin ) 
    $permissionName = PermissionsToString( $perms );
else if( $showUntracked && $perms = -99 ) // meleu: using -99 magic number for untracked (I know, it's sloppy)
    $permissionName = "Untracked";

$pageTitle = "User List";

$errorCode = seekGET( 'e' );
RenderDocType();
?>

<head>
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div id="userlist" class="left">

            <?php
            echo "<div class='navpath'>";
            echo "<b>All Users";

            if( $permissionName != NULL )
                echo " &raquo; $permissionName";

            echo "</b></div>";

            echo "<div class='largelist'>";
            echo "<h2 class='longheader'>User List:</h2>";

            echo "<p>Filter: ";

            if( $permissionName == NULL )
                echo "<b>All Users</b>";
            else
                echo "<a href='/userList.php?s=$sortBy'>All Users</a>";

            for( $i = \RA\Permissions::Unregistered; $i <= \RA\Permissions::Admin; $i++ )
            {
                echo " | ";

                if( $i == $perms && is_int( $perms ) )
                    echo "<b>" . PermissionsToString( $i ) . "</b>";
                else
                    echo "<a href='/userList.php?s=$sortBy&p=$i'>" . PermissionsToString( $i ) . "</a>";
            }
           echo "</p>";

            if( isset( $user ) && $permissions >= \RA\Permissions::Admin )
            {
                echo "<p>";
                echo "Filters for admins (always includes Untracked users):<br>";
                if( $permissionName == "Untracked" )
                    echo "<b>All Untracked users</b>";
                else
                    echo "<a href='/userList.php?s=$sortBy&u=1&p=-99'>All Untracked users</a>";

                for( $i = \RA\Permissions::Spam; $i <= \RA\Permissions::Admin; $i++ )
                {
                    echo " | ";

                    if( $i == $perms && is_int( $perms ) )
                        echo "<b>" . PermissionsToString( $i ) . "</b>";
                    else
                        echo "<a href='/userList.php?s=$sortBy&u=1&p=$i'>" . PermissionsToString( $i ) . "</a>";
                }
                echo "</p>";
            }


            echo "<table class='smalltable'><tbody>";

            $sort1 = ($sortBy == 1) ? 4 : 1;
            $sort2 = ($sortBy == 2) ? 5 : 2;
            $sort3 = ($sortBy == 3) ? 6 : 3;

            if( ($sortBy == 2 ) )
                echo "<th>Rank</th>";

            echo "<th colspan='2'><a href=\"/userList.php?s=$sort1&p=$perms\">User</a></th>";
            echo "<th><a href=\"/userList.php?s=$sort2&p=$perms\">Points</a></th>";
            echo "<th><a href=\"/userList.php?s=$sort3&p=$perms\">Num Achievements Earned</a></th>";

            $userCount = 0;
            foreach( $userListData as $userEntry )
            {
                if( $userCount++ % 2 == 0 )
                    echo "<tr>";
                else
                    echo "<tr class=\"alt\">";

                $nextUser = $userEntry[ 'User' ];
                $userBadge = "<a href=\"/User/" . $nextUser . "\"><img src=\"/UserPic/" . $nextUser . ".png\" width=32 height=32 alt=\"" . $nextUser . "\"></img></a>";
                $totalPoints = $userEntry[ 'RAPoints' ];
                $totalEarned = $userEntry[ 'NumAwarded' ];

                if( ($sortBy == 2 ) )
                {
                    echo "<td>";
                    echo $userCount + $offset;
                    echo "</td>";
                }

                echo "<td>";
                echo "$userBadge";
                echo "</td>";

                echo "<td class='user'>";
                //echo "<a href=\"/User/$nextUser\">$nextUser</a>";
                echo GetUserAndTooltipDiv( $nextUser, NULL, NULL, NULL, NULL, FALSE );
                echo "</td>";

                echo "<td>$totalPoints</td>";

                echo "<td>$totalEarned</td>";

                echo "</tr>";
            }
            echo "</tbody></table>";

            echo "<div class='rightalign row'>";
            if( $offset > 0 )
            {
                $prevOffset = $offset - $maxCount;
                echo "<a href='/userList.php?s=$sortBy&amp;o=$prevOffset&p=$perms'>&lt; Previous $maxCount</a> - ";
            }
            if( $userCount == $maxCount )
            {
                //	Max number fetched, i.e. there are more. Can goto next 25.
                $nextOffset = $offset + $maxCount;
                echo "<a href='/userList.php?s=$sortBy&amp;o=$nextOffset&p=$perms'>Next $maxCount &gt;</a>";
            }
            echo "</div>";

            echo "</div>";
            ?>

            <br/>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
