<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$sortBy = seekGet( 's' );
$offset = seekGet( 'o' );
$maxCount = 25;

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$userCount = getUserList( $sortBy, $offset, $maxCount, $userListData, $user );

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
            echo "<b>All Users</b>";
            echo "</div>";

            echo "<div class='largelist'>";

            echo "<h2 class='longheader'>User List:</h2>";
            echo "<table class='smalltable'><tbody>";

            $sort1 = ($sortBy == 1) ? 4 : 1;
            $sort2 = ($sortBy == 2) ? 5 : 2;
            $sort3 = ($sortBy == 3) ? 6 : 3;

            if( ($sortBy == 2 ) )
                echo "<th>Rank</th>";

            echo ">User</a></th>";
            echo "<th><a href=\"/userList.php?s=$sort2\">Points</a></th>";
            echo "<th><a href=\"/userList.php?s=$sort3\">Num Achievements Earned</a></th>";

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
                echo "<a href='/userList.php?s=$sortBy&amp;o=$prevOffset'>&lt; Previous $maxCount</a> - ";
            }
            if( $userCount == $maxCount )
            {
                //	Max number fetched, i.e. there are more. Can goto next 25.
                $nextOffset = $offset + $maxCount;
                echo "<a href='/userList.php?s=$sortBy&amp;o=$nextOffset'>Next $maxCount &gt;</a>";
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

