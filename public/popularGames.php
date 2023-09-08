<?php

return redirect(route('home'));

authenticateFromCookie($user, $permissions, $userDetails);

$maxCount = 50;

$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$method = requestInputSanitized('p', 0, 'integer');

$gameData = getMostPopularGames($offset, $count, $method);

$mobileBrowser = IsMobileBrowser();
RenderContentStart("Most Popular Games");
?>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<b>Most Popular Games</b>";
    echo "</div>";

    echo "<div class='detaillist'>";

    echo "<h3>Most Popular Games</h3>";

    // echo "Show: | ";
    //
    // if( $method==0 ) 	echo "by number of awards given";
    // else 				echo "<a href='/popularGames.php?p=0'>by number of awards given</a>";
    //
    // echo " | ";
    //
    // if( $method==1 )	echo "by unique members played ";
    // else				echo "<a href='/popularGames.php?p=1'>by unique members played</a> ";
    //
    // echo " | ";

    echo "<table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";

    echo "<th>Rank</th>";
    echo "<th>Game</th>";
    if (!$mobileBrowser) {
        echo "<th>Publisher</th>";
        echo "<th>Developer</th>";
    }
    echo "<th>Genre</th>";

    $countCol = ($method == 0) ? "Awards Given" : "Played By";
    echo "<th>$countCol</th>";

    echo "</tr>";

    $count = 0;

    foreach ($gameData as $gameEntry) {
        $count++;

        $gameID = $gameEntry['ID'];
        $gameTitle = $gameEntry['Title'];
        $gameForumTopicID = $gameEntry['ForumTopicID'];
        $gameFlags = $gameEntry['Flags'];
        $gameIcon = $gameEntry['ImageIcon'];
        $gamePublisher = $gameEntry['Publisher'];
        $gameDeveloper = $gameEntry['Developer'];
        $gameGenre = $gameEntry['Genre'];
        $gameReleased = $gameEntry['Released'];
        $consoleName = $gameEntry['ConsoleName'];
        $numRecords = $gameEntry['NumRecords'];

        echo "<tr>";

        echo "<td>";
        echo $count + $offset;
        echo "</td>";

        echo "<td style='min-width:30%'>";
        echo gameAvatar($gameEntry);
        echo "</td>";

        if (!$mobileBrowser) {
            echo "<td>";
            echo "$gamePublisher";
            echo "</td>";

            echo "<td>";
            echo "$gameDeveloper";
            echo "</td>";
        }

        echo "<td>";
        echo "$gameGenre";
        echo "</td>";

        echo "<td>";
        echo "$numRecords";
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "<div class='text-right'>";
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/popularGames.php?o=$prevOffset&amp;p=$method'>&lt; Previous $maxCount</a> - ";
    }
    if ($count == $maxCount) {
        // Max number fetched, i.e. there are more. Can goto next 25.
        $nextOffset = $offset + $maxCount;
        echo "<a href='/popularGames.php?o=$nextOffset&amp;p=$method'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
