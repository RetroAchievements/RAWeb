<?php

function RenderGameCompare($user, $gameID, $friendScores, $maxTotalPossibleForGame): void
{
    echo "<div id='gamecompare' class='component' >";
    echo "<h3>Friends</h3>";
    echo "<div class='nicebox'>";
    if (isset($friendScores)) {
        echo "Compare to your friend:<br>";
        echo "<table><tbody>";
        foreach ($friendScores as $friendScoreName => $friendData) {
            $link = "/gamecompare.php?ID=$gameID&f=$friendScoreName";

            echo "<tr>";
            echo "<td>";
            echo GetUserAndTooltipDiv($friendScoreName, true, $link);
            echo GetUserAndTooltipDiv($friendScoreName, false, $link);
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
        echo "Compare with any user:<br>";

        echo "<form method='get' action='/gamecompare.php'>";
        echo "<input type='hidden' name='ID' value='$gameID'>";
        echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' placeholder='Enter User...' />";
        echo "&nbsp;<input type='submit' value='Select' />";
        echo "</form>";
    } else {
        echo "RetroAchievements is a lot more fun with friends!<br><br>";
        if ($user == null) {
            echo "<a href='/createaccount.php'>Create an account</a> or login and start earning achievements today!<br>";
        } else {
            echo "Find friends to add <a href='/userList.php'>here</a>!<br>";
            echo "<br>";
            echo "or compare your progress in this game against any user:<br>";

            echo "<form method='get' action='/gamecompare.php'>";
            echo "<input type='hidden' name='ID' value='$gameID'>";
            echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' placeholder='Enter User...' />";
            echo "&nbsp;<input type='submit' value='Select' />";
            echo "</form>";
        }
    }
    echo "</div>";
    echo "</div>";
}
