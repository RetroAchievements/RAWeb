<?php

function RenderGameCompare(
    string $user,
    int $gameID,
    ?array $friendScores,
    int $maxTotalPossibleForGame
): void {
    echo "<div id='gamecompare' class='component' >";
    echo "<h2 class='text-h3'>Compare</h2>";
    echo "<div class='nicebox'>";
    if (isset($friendScores)) {
        if (!empty($friendScores)) {
            echo "Compare to a followed user:<br>";
            echo "<table class='table-highlight'><tbody>";
            foreach ($friendScores as $friendScoreName => $friendData) {
                $link = "/gamecompare.php?ID=$gameID&f=$friendScoreName";

                echo "<tr>";
                echo "<td>";
                echo userAvatar($friendScoreName, link: $link, iconSize: 16);
                echo "</td>";

                echo "<td>";
                echo "<a href='$link'>";
                echo $friendData['TotalPoints'] . "/$maxTotalPossibleForGame";
                echo "</a>";
                echo "</td>";

                echo "</tr>";
            }

            echo "</tbody></table>";
            echo "<br>";
        }

        echo "Compare with any user:<br>";

        echo "<form action='/gamecompare.php'>";
        echo "<input type='hidden' name='ID' value='$gameID'>";
        echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' placeholder='Enter User...' />";
        echo "&nbsp;<button class='btn'>Select</button>";
        echo "</form>";
    } else {
        echo "RetroAchievements is a lot more fun with others!<br><br>";
        if ($user == null) {
            echo "<a href='/createaccount.php'>Create an account</a> or login and start earning achievements today!<br>";
        } else {
            echo "Find users to follow <a href='/userList.php'>here</a>!<br>";
            echo "<br>";
            echo "or compare your progress in this game against any user:<br>";

            echo "<form action='/gamecompare.php'>";
            echo "<input type='hidden' name='ID' value='$gameID'>";
            echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' placeholder='Enter User...' />";
            echo "&nbsp;<button class='btn'>Select</button>";
            echo "</form>";
        }
    }
    echo "</div>";
    echo "</div>";
}
