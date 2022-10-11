<?php

use RA\SearchType;

authenticateFromCookie($user, $permissions, $userDetails);

$searchQuery = requestInputSanitized('s', null);
$searchType = requestInputSanitized('t', SearchType::All, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$maxCount = 50;

if (!SearchType::isValid($searchType)) {
    $searchType = SearchType::All;
}

$searchResults = [];
$resultsCount = 0;
if ($searchQuery !== null) {
    $resultsCount = performSearch($searchType, $searchQuery, $offset, $maxCount, $permissions, $searchResults);
}

RenderContentStart("Search");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<b>Search</b></a>";
        echo "</div>";

        echo "<h2>Search</h2>";

        echo "<div class='searchbox longer'>";
        echo "<form action='/searchresults.php'>";
        // echo "Search:&nbsp;";
        $searchQueryEscaped = attributeEscape($searchQuery);
        echo "<input size='42' name='s' type='text' class='searchboxinput' value='$searchQueryEscaped' placeholder='Search the site...' />";
        echo " in ";
        echo "<select name='t'>";
        foreach (SearchType::cases() as $t) {
            if ($t == $searchType) {
                echo "<option value='$t' selected>";
            } else {
                echo "<option value='$t'>";
            }
            echo SearchType::toString($t);
            echo "</option>";
        }
        echo "</select>";
        echo "&nbsp;&nbsp;";
        echo "<input type='submit' value='Search' />";
        echo "</form>";
        echo "</div>";

        if ($searchQuery !== null) {
            echo "<h4>Results:</h4>";
            if ($resultsCount == 0) {
                echo "No results found!";
            } else {
                echo "<table><tbody>";
                echo "<tr>";
                echo "<th>Type</th>";
                echo "<th colspan='2'>Match</th>";
                echo "</tr>";
                $lastType = '';
                $iter = 0;
                foreach ($searchResults as $nextResult) {
                    $nextType = $nextResult['Type'];
                    $nextID = $nextResult['ID'];
                    $nextTarget = $nextResult['Target'];
                    $nextTitle = attributeEscape(strip_tags($nextResult['Title']));

                    if ($nextType !== $lastType) {
                        $lastType = $nextType;
                    }

                    if ($iter++ % 2 == 0) {
                        echo "<tr>";
                    } else {
                        echo "<tr>";
                    }

                    echo "<td>$nextType</td>";
                    if ($nextType == 'User') {
                        echo "<td colspan='2'>";
                        echo userAvatar($nextID);
                        echo "</td>";
                    } elseif ($nextType == 'Achievement') {
                        $achData = GetAchievementData($nextID);
                        echo "<td colspan='2'>";
                        echo achievementAvatar($achData);
                        echo "</td>";
                    } elseif ($nextType == 'Game') {
                        $gameData = GetGameData($nextID);
                        echo "<td colspan='2'>";
                        echo gameAvatar($gameData);
                        echo "</td>";
                    } elseif ($nextType == 'Forum Comment' || $nextType == 'Comment') {
                        echo "<td>";
                        echo userAvatar($nextID);
                        echo "</td>";
                        echo "<td><a href='$nextTarget'>$nextTitle</a></td>";
                    } else {
                        echo "<td colspan=2><a href='$nextTarget'>$nextTitle</a></td>";
                    }

                    echo "</tr>";
                }

                echo "</tbody></table>";

                echo "<div class='float-right row'>";
                RenderPaginator($resultsCount, $maxCount, $offset, "/searchresults.php?s=$searchQueryEscaped&t=$searchType&o=");
                echo "</div>";
            }
        }
        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>
