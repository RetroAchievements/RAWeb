<?php

authenticateFromCookie($user, $permissions, $userDetails);

$maxCount = 50;

$consoleList = getConsoleList();
$consoleList[0] = 'All Consoles';
ksort($consoleList);                // Bump 'All Consoles' to the top

$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$method = requestInputSanitized('p', 0, 'integer');
$consoleID = requestInputSanitized('i', 0, 'integer');

$gameData = getGameListSearch($offset, $count, $method, $consoleID);

RenderContentStart("Game Search");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<b>Game Search</b>";    // NB. This will be a stub page
        echo "</div>";

        echo "<div class='detaillist'>";

        echo "<h3>Game Search</h3>";

        echo "<p class='embedded'>Showing: games by largest RetroRatio:</p>";

        echo "<p class='embedded'>Show: ";

        foreach ($consoleList as $nextConsoleID => $nextConsoleName) {
            if ($nextConsoleID > 0) {
                echo " | ";
            }

            sanitize_outputs($nextConsoleName);

            if ($nextConsoleID == $consoleID) {
                echo "<b>$nextConsoleName</b>";
            } else {
                echo "<a href='gameSearch.php?o=0&amp;p=$method&amp;i=$nextConsoleID'>$nextConsoleName</a>";
            }
        }

        echo "</p>";

        // echo "Show: | ";

        // if( $method==0 ) 	echo "by number of awards given";
        // else 				echo "<a href='/popularGames.php?p=0'>by number of awards given</a>";

        // echo " | ";

        // if( $method==1 )	echo "by unique members played ";
        // else				echo "<a href='/popularGames.php?p=1'>by unique members played</a> ";

        // echo " | ";

        echo "<table><tbody>";

        echo "<th>Rank</th>";
        echo "<th>Game</th>";
        echo "<th>Genre</th>";
        echo "<th>Publisher</th>";
        echo "<th>Developer</th>";
        echo "<th>Total Retro Ratio</th>";

        // $countCol = ( $method == 0 ) ? "Awards Given" : "Played By";
        // echo "<th>$countCol</th>";

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
            $gameTA = $gameEntry['TotalTruePoints'];
            $consoleName = $gameEntry['ConsoleName'];
            // $numRecords = $gameEntry['NumRecords'];

            sanitize_outputs(
                $gameTitle,
                $consoleName
            );

            echo "<tr>";

            echo "<td>";
            echo $count + $offset;
            echo "</td>";

            echo "<td style='min-width:30%'>";
            echo gameAvatar($gameEntry);
            echo "</td>";

            echo "<td>";
            echo "$gameGenre";
            echo "</td>";

            echo "<td>";
            echo "$gamePublisher";
            echo "</td>";

            echo "<td>";
            echo "$gameDeveloper";
            echo "</td>";

            echo "<td>";
            echo "$gameTA";
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody></table>";
        echo "</div>";

        echo "<div class='float-right row'>";
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            echo "<a href='/gameSearch.php?o=$prevOffset&amp;p=$method&amp;i=$consoleID'>&lt; Previous $maxCount</a> - ";
        }
        if ($count == $maxCount) {
            // Max number fetched, i.e. there are more. Can goto next 25.
            $nextOffset = $offset + $maxCount;
            echo "<a href='/gameSearch.php?o=$nextOffset&amp;p=$method&amp;i=$consoleID'>Next $maxCount &gt;</a>";
        }
        echo "</div>";

        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>
